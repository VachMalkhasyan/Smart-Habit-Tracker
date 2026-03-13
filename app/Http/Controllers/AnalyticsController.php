<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Completion;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $today = Carbon::today();

        // Heatmap — last year of completions
        $heatmap = $user->completions()
            ->where('completed_at', '>=', $today->copy()->subYear())
            ->where('is_done', true)
            ->selectRaw('completed_at, COUNT(*) as count')
            ->groupBy('completed_at')
            ->orderBy('completed_at')
            ->get()
            ->mapWithKeys(fn($c) => [
                Carbon::parse($c->completed_at)->format('Y-m-d') => $c->count
            ]);

        // Best day of week
        $dayStats = $user->completions()
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
            ->with('completions')
            ->get()
            ->map(function ($habit) {
                $total = $habit->completions->count();
                $done  = $habit->completions->where('is_done', true)->count();
                return [
                    'name'            => $habit->name,
                    'completion_rate' => $total > 0 ? round(($done / $total) * 100) : 0,
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
        ]);
    }
}
