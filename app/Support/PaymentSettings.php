<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\Payment;
use App\Models\Student;

class PaymentSettings
{
    public static function provider(): string
    {
        return trim((string) AppSetting::value('payment.provider', config('siwes.payments.provider', 'korapay')));
    }

    public static function setting(string $key, mixed $default = null): mixed
    {
        return AppSetting::value($key, $default) ?? $default;
    }

    public static function currency(): string
    {
        return trim((string) AppSetting::value('payment.currency', config('siwes.payments.currency', 'NGN')));
    }

    public static function ticketAmount(): int
    {
        return (int) AppSetting::value('payment.ticket_amount', config('siwes.payments.ticket_amount', 5000));
    }

    public static function ticketValidDays(): int
    {
        return (int) AppSetting::value('payment.ticket_valid_days', config('siwes.payments.ticket_valid_days', 30));
    }

    public static function workshopEnabled(): bool
    {
        return filter_var(AppSetting::value('payment.workshop_fee_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function workshopAmount(): int
    {
        return (int) AppSetting::value('payment.workshop_fee_amount', config('siwes.payments.workshop_fee_amount', 0));
    }

    public static function onlinePaymentAvailable(): bool
    {
        return self::provider() === 'korapay'
            && filled(AppSetting::value('korapay.secret_key', config('siwes.korapay.secret_key')))
            && filled(AppSetting::value('korapay.base_url', config('siwes.korapay.base_url')));
    }

    public static function studentHasPaidWorkshop(Student $student): bool
    {
        if (! self::workshopEnabled()) {
            return true;
        }

        return $student->payments()
            ->where('purpose', Payment::PURPOSE_WORKSHOP_FEE)
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->exists();
    }
}
