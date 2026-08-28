<?php

return [
    'security' => [
        'otp_ttl_minutes' => env('SIWES_OTP_TTL_MINUTES', 10),
        'otp_max_attempts' => env('SIWES_OTP_MAX_ATTEMPTS', 5),
        'headers_enabled' => env('SIWES_SECURITY_HEADERS_ENABLED', true),
    ],
    'payments' => [
        'provider' => env('SIWES_PAYMENT_PROVIDER', 'korapay'),
        'currency' => env('SIWES_PAYMENT_CURRENCY', 'NGN'),
        'ticket_amount' => (int) env('SIWES_TICKET_AMOUNT', 5000),
        'ticket_valid_days' => (int) env('SIWES_TICKET_VALID_DAYS', 30),
    ],
    'korapay' => [
        'base_url' => env('KORAPAY_BASE_URL', 'https://api.korapay.com/merchant/api/v1'),
        'public_key' => env('KORAPAY_PUBLIC_KEY'),
        'secret_key' => env('KORAPAY_SECRET_KEY'),
        'webhook_secret' => env('KORAPAY_WEBHOOK_SECRET', env('KORAPAY_SECRET_KEY')),
        'redirect_url' => env('KORAPAY_REDIRECT_URL'),
    ],
    'imports' => [
        'immediate_threshold' => (int) env('SIWES_IMPORT_IMMEDIATE_THRESHOLD', 2000),
        'cron_batch_size' => (int) env('SIWES_IMPORT_CRON_BATCH_SIZE', 1000),
        'cron_token' => env('SIWES_IMPORT_CRON_TOKEN'),
    ],
];
