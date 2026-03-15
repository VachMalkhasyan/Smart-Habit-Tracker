<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailVerificationCodeController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PomodoroController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\AiController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/layout', [DashboardController::class, 'updateLayout'])->name('dashboard.layout.update');
    Route::patch('/dashboard/note', [DashboardController::class, 'updateNote'])->name('dashboard.note.update');
    Route::post('/habits/reorder', [HabitController::class, 'reorder'])->name('habits.reorder');
    Route::resource('habits', HabitController::class);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/completions/{habit}/toggle',    [CompletionController::class, 'toggle'])->name('completions.toggle');
    Route::post('/completions/{habit}/increment', [CompletionController::class, 'increment'])->name('completions.increment');
    Route::post('/completions/{habit}/decrement', [CompletionController::class, 'decrement'])->name('completions.decrement');
});

Route::resource('categories', CategoryController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/settings',                [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings',               [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/habits',      [SettingsController::class, 'deleteHabits'])->name('settings.deleteHabits');
    Route::delete('/settings/account',     [SettingsController::class, 'deleteAccount'])->name('settings.deleteAccount');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/search', SearchController::class)->name('search');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/export/csv', [ExportController::class, 'csv'])->name('export.csv');
    Route::get('/export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');
});
Route::get('/analytics', [AnalyticsController::class, 'index'])
    ->name('analytics')
    ->middleware(['auth', 'verified']);

Route::post('/analytics/weekly-summary/generate', function (\Illuminate\Http\Request $request) {
    $user = $request->user();

    // Rate limit: only regenerate once per day
    if ($user->last_weekly_summary_date?->isToday()) {
        return response()->json([
            'summary' => $user->last_weekly_summary,
            'cached'  => true,
        ]);
    }

    $summary = app(\App\Services\AiService::class)->generateWeeklySummary($user);

    return response()->json(['summary' => $summary, 'cached' => false]);
})->middleware(['auth', 'verified'])->name('analytics.weekly-summary');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/templates',          [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{template}',[TemplateController::class, 'show'])->name('templates.show');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/friends',                          [FriendController::class, 'index'])->name('friends.index');
    Route::get('/friends/search',                   [FriendController::class, 'search'])->name('friends.search');
    Route::post('/friends/{user}/request',          [FriendController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/{friendship}/accept',     [FriendController::class, 'acceptRequest'])->name('friends.accept');
    Route::post('/friends/{friendship}/decline',    [FriendController::class, 'declineRequest'])->name('friends.decline');
    Route::delete('/friends/{user}/remove',         [FriendController::class, 'removeFriend'])->name('friends.remove');
    Route::post('/cheers/{completion}',             [FriendController::class, 'cheer'])->name('cheers.store');
    Route::delete('/cheers/{completion}',           [FriendController::class, 'removeCheer'])->name('cheers.remove');
    Route::get('/u/{user}',                         [FriendController::class, 'publicProfile'])->name('friends.profile');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify',       [EmailVerificationCodeController::class, 'show'])
        ->name('verification.notice');

    Route::post('/email/verify',      [EmailVerificationCodeController::class, 'verify'])
        ->name('verification.code.verify');

    Route::post('/email/resend',      [EmailVerificationCodeController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding',  [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
    Route::post('/onboarding/reset', [OnboardingController::class, 'reset'])->name('onboarding.reset');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pomodoro',                        [PomodoroController::class, 'index'])->name('pomodoro');
    Route::post('/pomodoro',                       [PomodoroController::class, 'store'])->name('pomodoro.store');
    Route::post('/pomodoro/{session}/complete',    [PomodoroController::class, 'complete'])->name('pomodoro.complete');
    Route::post('/pomodoro/{session}/abandon',     [PomodoroController::class, 'abandon'])->name('pomodoro.abandon');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications',                [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read',    [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all',     [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}',       [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('ai')->group(function () {
        Route::get('/coach', [AiController::class, 'page'])->name('ai.index');
        Route::get('/suggest-habits', [AiController::class, 'suggestHabits'])->name('ai.suggest-habits');
        Route::get('/conversations', [AiController::class, 'index'])->name('ai.conversations.index');
        Route::post('/conversations', [AiController::class, 'store'])->name('ai.conversations.store');
        Route::get('/conversations/{conversation}', [AiController::class, 'show'])->name('ai.conversations.show');
        Route::post('/conversations/{conversation}/chat', [AiController::class, 'chat'])->name('ai.conversations.chat');
        Route::delete('/conversations/{conversation}', [AiController::class, 'destroy'])->name('ai.conversations.destroy');
    });
});

