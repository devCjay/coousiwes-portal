<?php

namespace App\Notifications;

use App\Support\EmailTemplate;
use App\Support\MailConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpLoginNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
        private readonly int $ttlMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        MailConfiguration::apply();

        $replacements = [
            'name' => $notifiable->name,
            'code' => $this->code,
            'ttl_minutes' => $this->ttlMinutes,
            'app_name' => config('app.name', 'COOU SIWES Portal'),
        ];

        $body = EmailTemplate::lines(
            'login_otp',
            "Use {code} to complete your {app_name} sign in.\n\nThis code expires in {ttl_minutes} minutes. If you did not request this sign in, change your password immediately.",
            $replacements,
        );

        return (new MailMessage)
            ->subject(EmailTemplate::subject('login_otp', '{app_name} Login OTP', $replacements))
            ->greeting("Hello {$notifiable->name},")
            ->lines($body);
    }
}
