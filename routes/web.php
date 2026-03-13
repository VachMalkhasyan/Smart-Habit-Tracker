<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TemplateController;
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

});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
