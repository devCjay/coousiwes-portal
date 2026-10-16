<?php

namespace App\Notifications;

use App\Support\EmailTemplate;
use App\Support\MailConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceModeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly bool $maintenanceEnabled,
        private readonly string $portalUrl,
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

        $templateKey = $this->maintenanceEnabled ? 'maintenance_enabled' : 'maintenance_disabled';
        $fallbackSubject = $this->maintenanceEnabled
            ? 'COOU SIWES Portal Temporarily Closed'
            : 'COOU SIWES Portal Is Now Open';
        $fallbackBody = $this->maintenanceEnabled
            ? "The Portal is temporarily closed. Use the link below to access your portal and continue activities normally.\n\nPortal link: {portal_url}"
            : "The Portal is opened. You can now access the portal directly from the SIWES website.\n\nWebsite link: {portal_url}";
        $replacements = [
            'name' => $notifiable->name,
            'portal_url' => $this->portalUrl,
        ];

        return (new MailMessage)
            ->subject(EmailTemplate::subject($templateKey, $fallbackSubject, $replacements))
            ->greeting("Hello {$notifiable->name},")
            ->lines(EmailTemplate::lines($templateKey, $fallbackBody, $replacements))
            ->action(
                $this->maintenanceEnabled ? 'Access Your Portal' : 'Open SIWES Website',
                $this->portalUrl,
            )
            ->line("Alternative link: {$this->portalUrl}");
    }
}
