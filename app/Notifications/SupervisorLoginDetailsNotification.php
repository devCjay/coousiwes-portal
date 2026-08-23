<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorLoginDetailsNotification extends Notification
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
        return (new MailMessage)
            ->subject('COOU SIWES Supervisor Portal Login Details')
            ->greeting("Hello {$notifiable->name},")
            ->line('A supervisor account has been created for you on the COOU SIWES portal.')
            ->line("Email: {$notifiable->email}")
            ->line("Temporary password: {$this->temporaryPassword}")
            ->action('Login to Supervisor Portal', route('login'))
            ->line('Please sign in and change your password after your first login.');
    }
}
