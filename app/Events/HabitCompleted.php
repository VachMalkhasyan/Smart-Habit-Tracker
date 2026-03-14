<?php

namespace App\Events;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HabitCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User  $user,
        public Habit $habit,
        public bool  $isDone,
        public int   $currentStreak,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->user->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'habit_id'       => $this->habit->id,
            'habit_name'     => $this->habit->name,
            'is_done'        => $this->isDone,
            'current_streak' => $this->currentStreak,
        ];
    }

    public function broadcastAs(): string
    {
        return 'habit.completed';
    }
}
