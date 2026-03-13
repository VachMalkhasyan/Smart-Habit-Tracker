<?php

namespace App\Console\Commands;

use App\Mail\DailyHabitReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyHabitReminders extends Command
{
    protected $signature   = 'habits:daily-reminders';
    protected $description = 'Send daily habit reminder emails';

    public function handle(): void
    {
        $currentTime = now()->format('H:i');

        User::whereNotNull('email_verified_at')
            ->chunk(100, function ($users) use ($currentTime) {
                foreach ($users as $user) {
                    $settings = $user->settings ?? [];

                    // Skip if disabled
                    if (empty($settings['email_reminders'])) continue;

                    // Check if it matches user's preferred reminder time
                    $reminderTime = $settings['reminder_time'] ?? '08:00';
                    if ($reminderTime !== $currentTime) continue;

                    $habits = $user->habits()
                        ->where('status', 'active')
                        ->with('category')
                        ->orderBy('priority')
                        ->get();

                    if ($habits->isEmpty()) continue;

                    Mail::to($user->email)->send(
                        new DailyHabitReminder($user, $habits)
                    );

                    $this->info("Sent daily reminder to {$user->email}");
                }
            });
    }
}
