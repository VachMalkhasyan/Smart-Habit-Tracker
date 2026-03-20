<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user()?->fresh(),
            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
                'info'    => session('info'),
            ],
            'pendingFriendRequests' => $request->user()
                ? $request->user()->receivedFriendRequests()
                    ->where('status', 'pending')
                    ->count()
                : 0,
            'onboarding_completed' => $request->user()?->onboarding_completed ?? false,
            'xp_progress' => $request->user() ? \App\Services\XpService::progressToNextLevel($request->user()->fresh()) : null,
            'unread_notifications_count' => $request->user()
                ?->unreadNotifications()
                ->count() ?? 0,
            'today_mood'        => $request->user()?->todaysMood(),
            'daily_affirmation' => $request->user()?->daily_affirmation,
            'upcoming_interviews_today' => $request->user()
                ?->jobInterviews()
                ->whereDate('scheduled_at', today())
                ->where('outcome', 'pending')
                ->count() ?? 0,
            'has_cv' => $request->user()?->activeCV() !== null,
            'plan'        => $request->user()?->plan ?? 'free',
            'plan_limits' => $request->user()
                ? \App\Services\PlanService::limits($request->user())
                : config('plans.free'),
            'plan_name'   => $request->user()
                ? config('plans.' . $request->user()->plan . '.name', 'Free')
                : 'Free',
            'ai_messages_today' => $request->user()
                ? $request->user()->aiMessages()
                    ->where('role', 'user')
                    ->whereDate('ai_messages.created_at', today())
                    ->count()
                : 0,
            'habits_active_count' => $request->user()
                ? $request->user()->habits()->where('status', 'active')->count()
                : 0,
        ]);
    }
}
