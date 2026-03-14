<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class DailyCheckInMissed extends Notification implements ShouldBroadcastNow
{

    public function __construct(public string $affirmation) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'daily_missed',
            'title'   => 'We miss you!',
            'message' => $this->affirmation,
            'icon'    => '🌱',
            'url'     => '/dashboard',
            'meta'    => [
                'affirmation' => $this->affirmation,
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage($this->toArray($notifiable)))->onConnection('sync');
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }
}
