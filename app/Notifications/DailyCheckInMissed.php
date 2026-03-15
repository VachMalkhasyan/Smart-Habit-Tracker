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
        $aiService = app(\App\Services\AiService::class);
        
        // If an affirmation was passed, use it. Otherwise generate/fetch one from AI.
        $message = $this->affirmation ?: $aiService->generateDailyAffirmation($notifiable);

        return [
            'type'    => 'daily_missed',
            'title'   => 'We miss you!',
            'message' => $message,
            'icon'    => '🌱',
            'url'     => '/dashboard',
            'meta'    => [
                'affirmation' => $message,
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
