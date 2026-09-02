<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class MailConfiguration
{
    /**
     * Apply database-backed mail settings before runtime notification delivery.
     *
     * @return array{mailer: string, host: string, port: int, scheme: string, from_address: string, from_name: string}
     */
    public static function apply(): array
    {
        $rawMailer = self::scalarSettingValue('mail.mailer', config('mail.default', 'smtp'));
        $mailer = self::normalizeMailer($rawMailer);
        $host = self::scalarSettingValue('mail.host', config('mail.mailers.smtp.host'));
        $port = (int) self::scalarSettingValue('mail.port', config('mail.mailers.smtp.port'));
        $scheme = self::normalizeMailScheme(
            self::scalarSettingValue('mail.scheme', config('mail.mailers.smtp.scheme')),
            $port
        );
        $username = self::scalarSettingValue('mail.username', config('mail.mailers.smtp.username'));
        $password = self::scalarSettingValue('mail.password', config('mail.mailers.smtp.password'));
        $fromAddress = self::scalarSettingValue('mail.from_address', config('mail.from.address'));
        $fromName = self::scalarSettingValue('mail.from_name', config('mail.from.name', 'COOU SIWES Portal'));

        Config::set('mail.default', $mailer);
        Config::set("mail.mailers.{$mailer}.transport", $mailer);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.scheme', $scheme ?: null);
            Config::set('mail.mailers.smtp.username', $username ?: null);
            Config::set('mail.mailers.smtp.password', $password ?: null);
        }

        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName ?: 'COOU SIWES Portal');
        Mail::purge($mailer);

        return [
            'mailer' => $mailer,
            'host' => $host,
            'port' => $port,
            'scheme' => $scheme,
            'from_address' => $fromAddress,
            'from_name' => $fromName ?: 'COOU SIWES Portal',
        ];
    }

    public static function canSend(): bool
    {
        $settings = self::apply();

        if ($settings['from_address'] === '') {
            return false;
        }

        if ($settings['mailer'] !== 'smtp') {
            return true;
        }

        return $settings['host'] !== '' && $settings['port'] > 0;
    }

    private static function settingValue(string $key, mixed $default = null): mixed
    {
        $setting = AppSetting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    private static function scalarSettingValue(string $key, mixed $default = null): string
    {
        $value = self::settingValue($key, $default);

        if (is_array($value)) {
            $value = collect($value)->first(fn ($item): bool => is_scalar($item));
        }

        return trim((string) ($value ?? $default ?? ''));
    }

    private static function normalizeMailer(string $mailer): string
    {
        $mailer = strtolower(trim($mailer));
        $supported = ['smtp', 'sendmail', 'log', 'array'];

        return in_array($mailer, $supported, true) ? $mailer : 'smtp';
    }

    private static function normalizeMailScheme(string $scheme, int $port): string
    {
        $scheme = strtolower(trim($scheme));

        if (in_array($scheme, ['ssl', 'smtps'], true) || $port === 465) {
            return 'smtps';
        }

        return '';
    }
}
