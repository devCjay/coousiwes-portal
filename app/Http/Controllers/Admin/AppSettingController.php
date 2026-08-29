<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppSettingRequest;
use App\Models\AppSetting;
use App\Services\AuditLogger;
use App\Services\StudentImportService;
use App\Support\AjaxResponse;
use App\Support\PaymentSettings;
use App\Support\PortalPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Throwable;

class AppSettingController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        return view('pages.admin.settings', [
            'settings' => AppSetting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group'),
        ]);
    }

    public function store(StoreAppSettingRequest $request): JsonResponse|RedirectResponse
    {
        $payload = $this->payload($request);
        $setting = AppSetting::query()->create($payload);

        $this->auditLogger->record('settings.created', $request->user(), $request, $setting, $setting->only(['group', 'key', 'value', 'type']));

        return AjaxResponse::success($request, 'Setting created.');
    }

    public function update(StoreAppSettingRequest $request, AppSetting $appSetting): JsonResponse|RedirectResponse
    {
        $before = $appSetting->only(['group', 'key', 'value', 'type', 'is_public']);
        $appSetting->update($this->payload($request));
        $this->syncTicketPricingIfNeeded($appSetting->key);

        $this->auditLogger->record('settings.updated', $request->user(), $request, $appSetting, [
            'before' => $before,
            'after' => $appSetting->only(['group', 'key', 'value', 'type', 'is_public']),
        ]);

        return AjaxResponse::success($request, 'Setting updated.');
    }

    public function bulk(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.group' => ['required', 'string', 'max:80'],
            'settings.*.key' => ['required', 'string', 'max:120'],
            'settings.*.value' => ['nullable'],
            'settings.*.type' => ['required', 'string', Rule::in(['string', 'integer', 'boolean', 'decimal', 'json', 'array'])],
            'settings.*.is_public' => ['sometimes', 'boolean'],
            'settings.*.description' => ['nullable', 'string', 'max:1000'],
        ]);

        $saved = 0;

        foreach ($validated['settings'] as $settingPayload) {
            $settingPayload['is_public'] = filter_var($settingPayload['is_public'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $settingPayload['value'] = $this->castValue($settingPayload['value'] ?? null, $settingPayload['type']);

            $setting = AppSetting::query()->where('key', $settingPayload['key'])->first();
            $before = $setting?->only(['group', 'key', 'value', 'type', 'is_public']);

            if ($setting) {
                $setting->update($settingPayload);
                $this->syncTicketPricingIfNeeded($setting->key);
                $event = 'settings.updated';
                $metadata = [
                    'before' => $before,
                    'after' => $setting->only(['group', 'key', 'value', 'type', 'is_public']),
                ];
            } else {
                $setting = AppSetting::query()->create($settingPayload);
                $this->syncTicketPricingIfNeeded($setting->key);
                $event = 'settings.created';
                $metadata = $setting->only(['group', 'key', 'value', 'type']);
            }

            $this->auditLogger->record($event, $request->user(), $request, $setting, $metadata);
            $saved++;
        }

        return AjaxResponse::success($request, "{$saved} setting(s) saved.", reload: true);
    }

    public function testEmailConnection(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(PortalPermission::userHas($request->user(), 'settings.update'), 403);

        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ]);

        $rawMailer = $this->scalarSettingValue('mail.mailer', config('mail.default', 'smtp'));
        $mailer = $this->normalizeMailer($rawMailer);
        $host = $this->scalarSettingValue('mail.host', config('mail.mailers.smtp.host'));
        $port = (int) $this->scalarSettingValue('mail.port', config('mail.mailers.smtp.port'));
        $scheme = $this->normalizeMailScheme(
            $this->scalarSettingValue('mail.scheme', config('mail.mailers.smtp.scheme')),
            $port
        );
        $username = $this->scalarSettingValue('mail.username', config('mail.mailers.smtp.username'));
        $password = $this->scalarSettingValue('mail.password', config('mail.mailers.smtp.password'));
        $fromAddress = $this->scalarSettingValue('mail.from_address', config('mail.from.address'));
        $fromName = $this->scalarSettingValue('mail.from_name', config('mail.from.name', 'COOU SIWES Portal'));

        if ($mailer === 'smtp' && ($host === '' || $port <= 0)) {
            return AjaxResponse::error($request, 'SMTP host and port are required before testing email connection.');
        }

        if ($fromAddress === '') {
            return AjaxResponse::error($request, 'From address is required before sending a test email.');
        }

        Config::set('mail.default', $mailer);
        Config::set("mail.mailers.{$mailer}.transport", $mailer);
        Config::set("mail.mailers.{$mailer}.host", $host);
        Config::set("mail.mailers.{$mailer}.port", $port);
        Config::set("mail.mailers.{$mailer}.scheme", $scheme ?: null);
        Config::set("mail.mailers.{$mailer}.username", $username ?: null);
        Config::set("mail.mailers.{$mailer}.password", $password ?: null);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName ?: 'COOU SIWES Portal');
        Mail::purge($mailer);

        try {
            Mail::raw(
                "This is a COOU SIWES Portal email configuration test.\n\nIf you received this message, the portal can send email using the configured mail settings.",
                fn ($message) => $message
                    ->to($validated['test_email'])
                    ->subject('COOU SIWES Portal Email Test')
            );
        } catch (Throwable $exception) {
            $this->auditLogger->record('settings.email_test_failed', $request->user(), $request, metadata: [
                'mailer' => $mailer,
                'raw_mailer' => $rawMailer,
                'host' => $host,
                'port' => $port,
                'test_email' => $validated['test_email'],
                'error' => $exception->getMessage(),
            ]);

            return AjaxResponse::error($request, 'Email test failed: '.$exception->getMessage(), 500, 'email');
        }

        $this->auditLogger->record('settings.email_test_sent', $request->user(), $request, metadata: [
            'mailer' => $mailer,
            'raw_mailer' => $rawMailer,
            'host' => $host,
            'port' => $port,
            'test_email' => $validated['test_email'],
        ]);

        return AjaxResponse::success($request, "Test email sent to {$validated['test_email']}. Check the inbox and spam folder to confirm delivery.", reload: false);
    }

    public function clearCache(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(PortalPermission::userHas($request->user(), 'settings.view'), 403);

        $commands = ['optimize:clear'];

        if (array_key_exists('permission:cache-reset', Artisan::all())) {
            $commands[] = 'permission:cache-reset';
        }

        $results = [];

        try {
            foreach ($commands as $command) {
                Artisan::call($command);
                $results[$command] = trim(Artisan::output());
            }
        } catch (Throwable $exception) {
            $this->auditLogger->record('settings.cache_clear_failed', $request->user(), $request, metadata: [
                'commands' => $commands,
                'error' => $exception->getMessage(),
            ]);

            return AjaxResponse::error($request, 'Cache clear failed: '.$exception->getMessage(), 500, 'cache');
        }

        $this->auditLogger->record('settings.cache_cleared', $request->user(), $request, metadata: [
            'commands' => $commands,
            'results' => $results,
        ]);

        return AjaxResponse::success($request, 'System cache cleared successfully.', reload: true);
    }

    public function seedDatabase(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless(PortalPermission::isRootAdmin($request->user()), 403);

        $commands = [
            'migrate' => [
                '--force' => true,
            ],
            'db:seed' => [
                '--class' => 'Database\\Seeders\\DatabaseSeeder',
                '--force' => true,
            ],
        ];

        if (array_key_exists('permission:cache-reset', Artisan::all())) {
            $commands['permission:cache-reset'] = [];
        }

        $results = [];

        try {
            foreach ($commands as $command => $parameters) {
                Artisan::call($command, $parameters);
                $results[$command] = trim(Artisan::output());
            }
        } catch (Throwable $exception) {
            $this->auditLogger->record('settings.database_seed_failed', $request->user(), $request, metadata: [
                'commands' => array_keys($commands),
                'error' => $exception->getMessage(),
            ]);

            return AjaxResponse::error($request, 'Database migration/seeder update failed: '.$exception->getMessage(), 500, 'seeders');
        }

        $this->auditLogger->record('settings.database_seeded', $request->user(), $request, metadata: [
            'commands' => array_keys($commands),
            'results' => $results,
        ]);

        return AjaxResponse::success($request, 'Database migrations and seeders updated successfully.', reload: true);
    }

    public function processQueuedImports(Request $request, StudentImportService $studentImportService): JsonResponse|RedirectResponse
    {
        abort_unless(PortalPermission::userHas($request->user(), 'students.import'), 403);

        $batchSize = max(500, min((int) $this->settingValue('imports.cron_batch_size', config('siwes.imports.cron_batch_size', 1000)), 2000));
        $result = $studentImportService->processQueued($batchSize);

        $this->auditLogger->record('settings.student_import_cron_ran', $request->user(), $request, metadata: [
            'batch_size' => $batchSize,
            'result' => $result,
        ]);

        return AjaxResponse::success(
            $request,
            "Queued imports processed. {$result['processed_rows']} row(s) handled, {$result['completed_imports']} import(s) completed.",
            reload: true,
            data: $result,
        );
    }

    /**
     * @return array{group: string, key: string, value: mixed, type: string, is_public: bool, description?: string|null}
     */
    private function payload(StoreAppSettingRequest $request): array
    {
        $validated = $request->validated();
        $validated['is_public'] = $request->boolean('is_public');
        $validated['value'] = $this->castValue($validated['value'] ?? null, $validated['type']);

        return $validated;
    }

    private function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'decimal' => (float) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true, flags: JSON_THROW_ON_ERROR) : $value,
            default => $value,
        };
    }

    private function settingValue(string $key, mixed $default = null): mixed
    {
        $value = AppSetting::value($key, $default);

        return $value ?? $default;
    }

    private function scalarSettingValue(string $key, mixed $default = null): string
    {
        $value = $this->settingValue($key, $default);

        if (is_array($value)) {
            $value = collect($value)->first(fn ($item): bool => is_scalar($item));
        }

        return trim((string) ($value ?? $default ?? ''));
    }

    private function normalizeMailer(string $mailer): string
    {
        $mailer = strtolower(trim($mailer));
        $supported = ['smtp', 'sendmail', 'log', 'array'];

        return in_array($mailer, $supported, true) ? $mailer : 'smtp';
    }

    private function normalizeMailScheme(string $scheme, int $port): string
    {
        $scheme = strtolower(trim($scheme));

        if (in_array($scheme, ['ssl', 'smtps'], true) || $port === 465) {
            return 'smtps';
        }

        return '';
    }

    private function syncTicketPricingIfNeeded(string $key): void
    {
        if (! in_array($key, ['payment.ticket_amount', 'payment.currency'], true)) {
            return;
        }

        PaymentSettings::syncUnusedTicketPricing();
    }
}
