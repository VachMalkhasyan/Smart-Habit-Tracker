<?php

namespace App\Console\Commands;

use App\Mail\DailyHabitReminder;
use App\Models\User;
use App\Notifications\DailyCheckInMissed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyHabitReminders extends Command
{
    protected $signature   = 'habits:daily-reminders';
    protected $description = 'Send daily habit reminder emails';

    public function handle(): void
    {
        $currentTime = now()->format('H:i');
        $today       = now()->toDateString();

        $affirmations = [
            "Every day is a fresh start 🌱",
            "Small steps still move you forward 💪",
            "Progress over perfection, always ✨",
            "Your future self is cheering you on 🌟",
            "One habit at a time changes everything 🔥",
            "You've got this — we believe in you 💙",
        ];

        User::whereNotNull('email_verified_at')
            ->chunk(100, function ($users) use ($currentTime, $today, $affirmations) {
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

                    // Send email reminder
                    Mail::to($user->email)->send(
                        new DailyHabitReminder($user, $habits)
                    );

                    // Check if user has completed 0 habits today — send DailyCheckInMissed notification
                    $doneToday = $user->completions()
                        ->whereDate('completed_at', $today)
                        ->where('is_done', true)
                        ->count();

                    if ($doneToday === 0) {
                        $affirmation = $affirmations[array_rand($affirmations)];
                        $user->notify(new DailyCheckInMissed($affirmation));
                    }

                    $this->info("Sent daily reminder to {$user->email}");
                }
            });
    }
}
