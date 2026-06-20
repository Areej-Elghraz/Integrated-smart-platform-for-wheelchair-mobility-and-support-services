<?php

namespace App\Notifications\DatabaseNotification;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SosAlertNotification extends Notification
{
    use Queueable;

    public User $sender;
    public array $payload;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, array $payload)
    {
        $this->sender = $sender;
        $this->payload = $payload;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sos_alert',
            'message' => "{$this->sender->name} is in an emergency and needs help!",
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_image' => $this->sender->image,
            'latitude' => $this->payload['latitude'] ?? null,
            'longitude' => $this->payload['longitude'] ?? null,
            'location_link' => $this->payload['location_link'] ?? null,
            'triggered_at' => $this->payload['triggered_at'] ?? now()->toISOString(),
        ];
    }
}