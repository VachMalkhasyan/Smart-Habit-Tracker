<?php

namespace App\Notifications;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LeveledUp extends Notification implements ShouldBroadcastNow
{

    public function __construct(
        public int    $newLevel,
        public string $levelTitle,
        public int    $xpTotal
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'leveled_up',
            'title'   => 'Level Up!',
            'message' => "You reached Level {$this->newLevel} — {$this->levelTitle}!",
            'icon'    => '🏆',
            'url'     => '/dashboard',
            'meta'    => [
                'new_level'   => $this->newLevel,
                'level_title' => $this->levelTitle,
                'xp_total'    => $this->xpTotal,
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
