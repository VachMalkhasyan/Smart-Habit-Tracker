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

        ]);
    }
}
