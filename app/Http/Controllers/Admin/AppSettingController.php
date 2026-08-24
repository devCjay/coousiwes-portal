<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAppSettingRequest;
use App\Models\AppSetting;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use App\Support\PortalPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
                $event = 'settings.updated';
                $metadata = [
                    'before' => $before,
                    'after' => $setting->only(['group', 'key', 'value', 'type', 'is_public']),
                ];
            } else {
                $setting = AppSetting::query()->create($settingPayload);
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

        $host = (string) $this->settingValue('mail.host', config('mail.mailers.smtp.host'));
        $port = (int) $this->settingValue('mail.port', config('mail.mailers.smtp.port'));
        $scheme = (string) $this->settingValue('mail.scheme', config('mail.mailers.smtp.scheme'));
        $timeout = 8;

        if ($host === '' || $port <= 0) {
            return AjaxResponse::error($request, 'SMTP host and port are required before testing email connection.');
        }

        $target = ($scheme === 'smtps' ? 'ssl://' : '').$host.':'.$port;
        $errno = 0;
        $error = '';
        $connection = @stream_socket_client($target, $errno, $error, $timeout);

        if (! is_resource($connection)) {
            return AjaxResponse::error($request, "SMTP connection failed: {$error}", key: 'email');
        }

        fclose($connection);

        return AjaxResponse::success($request, "SMTP connection to {$host}:{$port} succeeded.");
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

            return AjaxResponse::error($request, 'Database seeder update failed: '.$exception->getMessage(), 500, 'seeders');
        }

        $this->auditLogger->record('settings.database_seeded', $request->user(), $request, metadata: [
            'commands' => array_keys($commands),
            'results' => $results,
        ]);

        return AjaxResponse::success($request, 'Database seeders imported and updated successfully.', reload: true);
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
}
