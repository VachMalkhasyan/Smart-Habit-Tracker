<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummary;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWeeklySummaries extends Command
{
    protected $signature   = 'habits:weekly-summaries';
    protected $description = 'Send weekly habit summary emails';

    public function handle(): void
    {
        $today     = strtolower(now()->format('l')); // e.g. "monday"
        $weekStart = now()->subDays(7)->toDateString();
        $weekEnd   = now()->toDateString();

        User::whereNotNull('email_verified_at')
            ->chunk(100, function ($users) use ($today, $weekStart, $weekEnd) {
                foreach ($users as $user) {
                    try {
                        $settings = $user->settings ?? [];

                        // Skip if disabled
                        if (empty($settings['weekly_summary'])) continue;

                        // Check if today matches user's chosen day
                        $summaryDay = $settings['weekly_summary_day'] ?? 'monday';
                        if ($summaryDay !== $today) continue;

                        $habits = $user->habits()
                            ->where('status', 'active')
                            ->with(['completions' => fn($q) =>
                            $q->whereBetween('completed_at', [$weekStart, $weekEnd])
                            ])
                            ->get();

                        if ($habits->isEmpty()) continue;

                        // Overall stats
                        $totalPossible   = $habits->count() * 7;
                        $totalCompleted  = $habits->sum(fn($h) =>
                        $h->completions->where('is_done', true)->count()
                        );

                        $stats = [
                            'completions'    => $totalCompleted,
                            'completion_rate'=> $totalPossible > 0
                                ? round(($totalCompleted / $totalPossible) * 100)
                                : 0,
                            'best_streak'    => $habits->max('longest_streak') ?? 0,
                            'active_habits'  => $habits->count(),
                        ];

                        // Per-habit stats
                        $habitStats = $habits->map(function ($habit) {
                            $completed = $habit->completions->where('is_done', true)->count();
                            return [
                                'name'      => $habit->name,
                                'completed' => $completed,
                                'possible'  => 7,
                                'rate'      => round(($completed / 7) * 100),
                            ];
                        })->sortByDesc('rate')->values()->toArray();

                        Mail::to($user->email)->send(
                            new WeeklySummary($user, $stats, $habitStats)
                        );

                        $this->info("Sent weekly summary to {$user->email}");
                    } catch (\Exception $e) {
                        \Log::error("Weekly summary failed for user {$user->id}: " . $e->getMessage());
                        $this->error("Failed for user {$user->id}: " . $e->getMessage());
                    }
                }
            });
    }
}
