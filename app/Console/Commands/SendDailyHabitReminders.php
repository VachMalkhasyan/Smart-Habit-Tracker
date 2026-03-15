<?php

namespace App\Console\Commands;

use App\Mail\DailyHabitReminder;
use App\Models\User;
use App\Notifications\DailyCheckInMissed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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
                    try {
                        $settings = $user->settings ?? [];

                        // Skip if user disabled reminders
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

                        // Check if user has completed ALL habits today
                        $completedToday = $user->completions()
                            ->whereDate('completed_at', $today)
                            ->where('is_done', true)
                            ->count();

                        if ($completedToday >= $habits->count()) continue; // All done, no alert needed

                        // Send email reminder
                        Mail::to($user->email)->send(
                            new DailyHabitReminder($user, $habits)
                        );

                        // If 0 habits completed, also send the mobile/in-app notification
                        if ($completedToday === 0) {
                            $affirmation = $affirmations[array_rand($affirmations)];
                            $user->notify(new DailyCheckInMissed($affirmation));
                        }

                        $this->info("Sent daily reminder to {$user->email}");
                    } catch (\Exception $e) {
                        \Log::error("Daily reminder failed for user {$user->id}: " . $e->getMessage());
                        $this->error("Failed for user {$user->id}: " . $e->getMessage());
                    }
                }
            });
    }
}
