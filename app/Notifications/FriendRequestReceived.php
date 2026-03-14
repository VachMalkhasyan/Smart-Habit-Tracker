<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class FriendRequestReceived extends Notification implements ShouldBroadcastNow
{

    public function __construct(public User $sender) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'friend_request',
            'title'   => 'Friend Request',
            'message' => "{$this->sender->name} sent you a friend request",
            'icon'    => '👋',
            'url'     => '/friends',
            'meta'    => [
                'sender_id'     => $this->sender->id,
                'sender_avatar' => $this->sender->profile_photo_url,
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
