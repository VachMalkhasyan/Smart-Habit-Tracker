<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('habits:missed-reminders')->dailyAt('09:00');

Schedule::command('habits:daily-reminders')->everyMinute();

Schedule::command('habits:weekly-summaries')->dailyAt('09:00');

// Generate daily affirmations for all users at midnight
Schedule::call(function () {
    \App\Models\User::whereNotNull('email_verified_at')
        ->chunk(100, function ($users) {
            foreach ($users as $user) {
                app(\App\Services\AiService::class)->generateDailyAffirmation($user);
            }
        });
})->dailyAt('00:00')->name('ai:daily-affirmations');
