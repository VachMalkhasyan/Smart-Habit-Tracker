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

        // Monthly data (last 6 months) for MonthlyTrendWidget
        $monthlyTrend = collect(range(5, 0))->map(function ($monthsAgo) use ($user) {
            $date = Carbon::today()->startOfMonth()->subMonths($monthsAgo);
            return [
                'month'     => $date->format('M'),
                'completed' => $user->completions()
                    ->whereYear('completed_at', $date->year)
                    ->whereMonth('completed_at', $date->month)
                    ->where('is_done', true)
                    ->count(),
            ];
        });

        // Friends data for PinnedFriendWidget
        $friends = $user->friends()->map(function ($friend) {
            $friend->load(['completions' => function ($q) {
                $q->with('habit', 'cheers.user')
                  ->where('is_done', true)
                  ->orderBy('completed_at', 'desc')
                  ->take(5);
            }]);
            return $friend;
        });

        $defaultLayout = [
            ['id' => 'stat-active', 'type' => 'StatCardWidget', 'w' => 3, 'h' => 'auto', 'config' => ['statType' => 'active_habits']],
            ['id' => 'stat-done', 'type' => 'StatCardWidget', 'w' => 3, 'h' => 'auto', 'config' => ['statType' => 'done_today']],
            ['id' => 'stat-completed', 'type' => 'StatCardWidget', 'w' => 3, 'h' => 'auto', 'config' => ['statType' => 'completed_all']],
            ['id' => 'stat-streak', 'type' => 'StatCardWidget', 'w' => 3, 'h' => 'auto', 'config' => ['statType' => 'longest_streak']],
            ['id' => 'today-habits', 'type' => 'TodayHabitsWidget', 'w' => 8, 'h' => 'auto', 'is_core' => true],
            ['id' => 'weekly-progress', 'type' => 'WeeklyProgressWidget', 'w' => 4, 'h' => 'auto', 'is_core' => true],
        ];

        $dashboardLayout = $user->settings['dashboard_layout'] ?? $defaultLayout;

        return Inertia::render('Dashboard', [
            'habits'         => $habits,
            'weeklyData'     => $weeklyData,
            'monthlyTrend'   => $monthlyTrend,
            'friends'        => $friends,
            'dashboardLayout'=> $dashboardLayout,
            'totalActive'    => $user->habits()->where('status', 'active')->count(),
            'totalCompleted' => $user->habits()->where('status', 'completed')->count(),
            'longestStreak'  => $user->habits()->max('longest_streak') ?? 0,
            'todayCompleted' => $user->completions()->whereDate('completed_at', today())->where('is_done', true)->count(),
        ]);
    }

    public function updateLayout(Request $request)
    {
        $request->validate([
            'dashboard_layout' => 'required|array',
        ]);

        $user = $request->user();
        $settings = $user->settings ?? [];
        $settings['dashboard_layout'] = $request->dashboard_layout;
        $user->settings = $settings;
        $user->save();

        return redirect()->back();
    }
}
