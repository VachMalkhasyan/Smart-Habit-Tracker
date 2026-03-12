<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\SettingsController;
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
