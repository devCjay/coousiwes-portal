<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PortalNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => (string) ($this->data['title'] ?? 'Portal notification'),
            'message' => (string) ($this->data['message'] ?? 'A portal event requires your attention.'),
            'tone' => (string) ($this->data['tone'] ?? 'info'),
            'action_url' => $this->data['action_url'] ?? null,
            'meta' => $this->data['meta'] ?? [],
        ];
    }
}
