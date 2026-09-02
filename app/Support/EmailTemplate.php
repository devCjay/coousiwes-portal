<?php

namespace App\Support;

use App\Models\AppSetting;

class EmailTemplate
{
    /**
     * @param  array<string, mixed>  $replacements
     */
    public static function subject(string $key, string $fallback, array $replacements = []): string
    {
        return self::render((string) AppSetting::value("mail.templates.{$key}.subject", $fallback), $replacements);
    }

    /**
     * @param  array<string, mixed>  $replacements
     */
    public static function body(string $key, string $fallback, array $replacements = []): string
    {
        return self::render((string) AppSetting::value("mail.templates.{$key}.body", $fallback), $replacements);
    }

    /**
     * @param  array<string, mixed>  $replacements
     * @return array<int, string>
     */
    public static function lines(string $key, string $fallback, array $replacements = []): array
    {
        return collect(preg_split('/\R+/', self::body($key, $fallback, $replacements)) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $replacements
     */
    public static function render(string $template, array $replacements): string
    {
        $tokens = [];

        foreach ($replacements as $key => $value) {
            $tokens['{'.$key.'}'] = (string) $value;
        }

        return strtr($template, $tokens);
    }
}
