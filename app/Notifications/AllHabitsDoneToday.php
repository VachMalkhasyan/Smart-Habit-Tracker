<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AllHabitsDoneToday extends Notification implements ShouldBroadcastNow
{

    public function __construct(public int $totalHabits) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'all_habits_done',
            'title'   => 'Perfect Day!',
            'message' => 'Amazing! You completed all your habits today. Keep the momentum going!',
            'icon'    => '🎯',
            'url'     => '/dashboard',
            'meta'    => [
                'total_habits' => $this->totalHabits,
                'date'         => Carbon::today()->toDateString(),
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
