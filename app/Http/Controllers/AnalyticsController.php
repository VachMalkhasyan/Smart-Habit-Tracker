<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Completion;
use App\Services\PlanService;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        $daysLimit = PlanService::limit($user, 'analytics_days');
        $startDate = $daysLimit === -1
            ? $user->created_at->toDateString()
            : now()->subDays($daysLimit)->toDateString();

        // Heatmap — limited by plan
        $heatmap = Completion::whereIn('habit_id', $user->habits()->pluck('id'))
            ->where('completed_at', '>=', $startDate)
            ->where('is_done', true)
            ->selectRaw('completed_at, COUNT(*) as count')
            ->groupBy('completed_at')
            ->orderBy('completed_at')
            ->get()
            ->mapWithKeys(fn($c) => [
                Carbon::parse($c->completed_at)->format('Y-m-d') => $c->count
            ]);

        // Best day of week
        $dayStats = Completion::whereIn('habit_id', $user->habits()->pluck('id'))
            ->where('completed_at', '>=', $startDate)
            ->where('is_done', true)
            ->selectRaw('DAYOFWEEK(completed_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->mapWithKeys(fn($d) => [
                Carbon::now()->startOfWeek()->addDays($d->day - 2)->format('D') => $d->count
            ]);

        // Monthly trend (last 6 months)
        $monthlyTrend = collect(range(5, 0))->map(function ($monthsAgo) use ($user, $today) {
            $date = $today->copy()->subMonths($monthsAgo);
            return [
                'month' => $date->format('M'),
                'total' => $user->completions()
                    ->whereYear('completed_at', $date->year)
                    ->whereMonth('completed_at', $date->month)
                    ->where('is_done', true)
                    ->count(),
            ];
        });

        // Per habit completion rates
        $habitStats = $user->habits()
            ->with(['completions' => fn($q) => $q->where('completed_at', '>=', $startDate)])
            ->get()
            ->map(function ($habit) use ($startDate) {
                $done = $habit->completions->where('is_done', true)->count();
                
                // Denom should be days habit was active within the period
                $effectiveStart = $habit->start_date->max(Carbon::parse($startDate));
                $daysActive = $effectiveStart->diffInDays(now()) + 1;
                
                return [
                    'name'            => $habit->name,
                    'completion_rate' => $daysActive > 0 ? round(($done / $daysActive) * 100) : 0,
                    'current_streak'  => $habit->current_streak,
                    'longest_streak'  => $habit->longest_streak,
                    'total_completions' => $done,
                ];
            })
            ->sortByDesc('completion_rate')
            ->values();

        // Overview stats
        $totalCompletions = $user->completions()->where('is_done', true)->count();
        $activeDays       = $user->completions()
            ->where('is_done', true)
            ->distinct('completed_at')
            ->count('completed_at');
        $bestStreak       = $user->habits()->max('longest_streak') ?? 0;
        $avgCompletionRate = $habitStats->avg('completion_rate') ?? 0;

        return Inertia::render('Analytics/Index', [
            'heatmap'          => $heatmap,
            'dayStats'         => $dayStats,
            'monthlyTrend'     => $monthlyTrend,
            'habitStats'       => $habitStats,
            'totalCompletions' => $totalCompletions,
            'activeDays'       => $activeDays,
            'bestStreak'       => $bestStreak,
            'avgCompletionRate' => round($avgCompletionRate),
            'weekly_summary'    => $user->last_weekly_summary,
            'average_mood'      => $user->weeklyMoodAverage(),
            'mood_streak'       => $user->getMoodStreak(),
        ]);
    }
}
