<?php

namespace App\Events;

use App\Models\User;
use App\Models\Habit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendActivityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User  $friend,
        public Habit $habit,
        public bool  $isDone,
    ) {}

    public function broadcastOn(): array
    {
        // Broadcast to all accepted friends
        return $this->friend->friends()
            ->get()
            ->map(fn($f) => new PrivateChannel("user.{$f->id}"))
            ->toArray();
    }

    public function broadcastWith(): array
    {
        return [
            'friend_id'   => $this->friend->id,
            'friend_name' => $this->friend->name,
            'friend_avatar' => $this->friend->profile_photo_url,
            'habit_name'  => $this->habit->name,
            'is_done'     => $this->isDone,
            'timestamp'   => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'friend.activity';
    }
}
