<?php

namespace App\Notifications;

use App\Support\EmailTemplate;
use App\Support\MailConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLoginDetailsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $temporaryPassword,
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
            'email' => $notifiable->email,
            'temporary_password' => $this->temporaryPassword,
            'login_url' => route('login.admin'),
        ];

        $body = EmailTemplate::lines(
            'admin_login_details',
            "An admin account has been created for you on the COOU SIWES portal.\n\nEmail: {email}\nTemporary password: {temporary_password}\n\nPlease sign in and change your password after your first login.",
            $replacements,
        );

        return (new MailMessage)
            ->subject(EmailTemplate::subject('admin_login_details', 'COOU SIWES Admin Portal Login Details', $replacements))
            ->greeting("Hello {$notifiable->name},")
            ->lines($body)
            ->action('Login to Admin Portal', route('login.admin'));
    }
}
