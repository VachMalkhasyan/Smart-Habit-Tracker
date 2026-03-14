<?php

namespace App\Events;

use App\Models\User;
use App\Services\XpService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class XpAwarded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User   $user,
        public int    $amount,
        public string $reason,
        public bool   $leveledUp,
        public int    $newLevel,
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
            'amount'      => $this->amount,
            'reason'      => $this->reason,
            'leveled_up'  => $this->leveledUp,
            'new_level'   => $this->newLevel,
            'xp_progress' => XpService::progressToNextLevel($this->user->fresh()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'xp.awarded';
    }
}
