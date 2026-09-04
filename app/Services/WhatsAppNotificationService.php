<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Support\EmailTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppNotificationService
{
    /**
     * @param  array<string, mixed>  $replacements
     */
    public function send(string|null $phone, string $templateKey, string $fallback, array $replacements = []): bool
    {
        if (! $this->enabled() || blank($phone)) {
            return false;
        }

        $token = $this->setting('whatsapp.access_token');
        $phoneNumberId = $this->setting('whatsapp.phone_number_id');
        $apiVersion = $this->setting('whatsapp.api_version', 'v21.0');

        if ($token === '' || $phoneNumberId === '') {
            return false;
        }

        $to = $this->normalizePhone($phone);
        $message = EmailTemplate::render(
            (string) AppSetting::value("whatsapp.templates.{$templateKey}.body", $fallback),
            $replacements,
        );

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(15)
                ->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('WhatsApp notification failed.', [
                'template' => $templateKey,
                'status' => $response->status(),
                'body' => $response->json() ?: $response->body(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp notification exception.', [
                'template' => $templateKey,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function enabled(): bool
    {
        return filter_var(AppSetting::value('whatsapp.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function setting(string $key, mixed $default = ''): string
    {
        $value = AppSetting::value($key, $default);

        if (is_array($value)) {
            $value = collect($value)->first(fn ($item): bool => is_scalar($item));
        }

        return trim((string) ($value ?? $default));
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($phone, '0')) {
            return $this->setting('whatsapp.default_country_code', '234').substr($phone, 1);
        }

        return $phone;
    }
}
