<?php

namespace App\Console\Commands;

use App\Mail\MissedHabitReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMissedHabitReminders extends Command
{
    protected $signature   = 'habits:missed-reminders';
    protected $description = 'Send missed habit reminder emails';

    public function handle(): void
    {
        $yesterday = now()->subDay()->toDateString();

        User::whereNotNull('email_verified_at')
            ->chunk(100, function ($users) use ($yesterday) {
                foreach ($users as $user) {
                    $settings = $user->settings ?? [];

                    // Skip if user disabled missed alerts
                    if (empty($settings['missed_habit_alerts'])) continue;

                    $activeHabits = $user->habits()
                        ->where('status', 'active')
                        ->with('category')
                        ->get();

                    if ($activeHabits->isEmpty()) continue;

                    // Find habits that were NOT completed yesterday
                    $missedHabits = $activeHabits->filter(function ($habit) use ($yesterday) {
                        return !$habit->completions()
                            ->whereDate('completed_at', $yesterday)
                            ->where('is_done', true)
                            ->exists();
                    });

                    if ($missedHabits->isEmpty()) continue;

                    Mail::to($user->email)->send(
                        new MissedHabitReminder($user, $missedHabits)
                    );

                    $this->info("Sent missed reminder to {$user->email} ({$missedHabits->count()} habits)");
                }
            });
    }
}
