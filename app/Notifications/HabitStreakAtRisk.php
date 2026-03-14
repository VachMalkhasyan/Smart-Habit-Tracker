<?php

namespace App\Notifications;

use App\Models\Habit;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class HabitStreakAtRisk extends Notification implements ShouldBroadcastNow
{

    public function __construct(public Habit $habit) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'streak_at_risk',
            'title'   => 'Streak at risk!',
            'message' => "Your '{$this->habit->name}' streak of {$this->habit->current_streak} days is at risk — complete it today!",
            'icon'    => '🔥',
            'url'     => "/habits/{$this->habit->id}",
            'meta'    => [
                'habit_id'    => $this->habit->id,
                'streak_days' => $this->habit->current_streak,
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
