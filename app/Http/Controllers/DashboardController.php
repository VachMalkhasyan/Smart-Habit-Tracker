<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $habits = $user->habits()
            ->with(['category', 'completions' => function ($q) {
                $q->whereDate('completed_at', today());
            }])
            ->where('status', 'active')
            ->orderBy('priority')
            ->get()
            ->map(function ($habit) {
                $todayCompletion = $habit->completions->first();
                $habit->today_count = $todayCompletion?->count ?? 0;
                $habit->is_done_today = $todayCompletion?->is_done ?? false;
                return $habit;
            });

        // Weekly data (last 7 days)
        $weeklyData = collect(range(6, 0))->map(function ($daysAgo) use ($user) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'day'       => $date->format('D'),
                'completed' => $user->completions()
                    ->whereDate('completed_at', $date)
                    ->where('is_done', true)
                    ->count(),
            ];
        });

        return Inertia::render('Dashboard', [
            'habits'         => $habits,
            'weeklyData'     => $weeklyData,
            'totalActive'    => $user->habits()->where('status', 'active')->count(),
            'totalCompleted' => $user->habits()->where('status', 'completed')->count(),
            'longestStreak'  => $user->habits()->max('longest_streak') ?? 0,
            'todayCompleted' => $user->completions()->whereDate('completed_at', today())->where('is_done', true)->count(),
        ]);
    }
}
