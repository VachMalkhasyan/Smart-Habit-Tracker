<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FriendCheeredCompletion extends Notification implements ShouldBroadcastNow
{

    public function __construct(
        public User   $cheerer,
        public string $habitName,
        public string $emoji
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'friend_cheered',
            'title'   => 'Someone cheered you!',
            'message' => "{$this->cheerer->name} cheered your {$this->habitName} completion",
            'icon'    => '🎉',
            'url'     => '/friends',
            'meta'    => [
                'emoji'      => $this->emoji,
                'habit_name' => $this->habitName,
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
