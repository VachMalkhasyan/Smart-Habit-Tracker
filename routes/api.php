<?php

use App\Models\Habit;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\HabitTemplate;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/templates', function () {
    return HabitTemplate::orderBy('category')->orderBy('sort_order')->get();
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/extension/me', function (Request $request) {
        $user   = $request->user()->fresh();
        $habits = $user->habits()
            ->where('status', 'active')
            ->with(['completions' => fn($q) => $q->whereDate('completed_at', today())])
            ->orderBy('priority')
            ->get()
            ->map(fn($h) => [
                'id'              => $h->id,
                'name'            => $h->name,
                'current_streak'  => $h->current_streak,
                'completed_today' => $h->completions->where('is_done', true)->count() > 0,
            ]);

        return response()->json([
            'name'           => $user->name,
            'level'          => $user->level,
            'xp'             => $user->xp,
            'xp_progress'    => XpService::progressToNextLevel($user),
            'dashboard_note' => $user->dashboard_note,
            'habits'         => $habits,
            'unread_notifications' => $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($n) => [
                    'id'      => $n->id,
                    'icon'    => $n->data['icon'],
                    'title'   => $n->data['title'],
                    'message' => $n->data['message'],
                    'url'     => $n->data['url'],
                ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    });

    Route::post('/extension/habits/{habit}/toggle', function (Request $request, Habit $habit) {
        $user       = $request->user();
        $today      = now()->toDateString();
        $completion = $habit->completions()->firstOrCreate(
            ['completed_at' => $today],
            ['user_id' => $user->id, 'count' => 0, 'is_done' => false]
        );

        $completion->update([
            'is_done' => !$completion->is_done,
            'count'   => !$completion->is_done ? $habit->repeat_count : 0,
        ]);

        $xpResult = null;
        if ($completion->is_done) {
            $xpResult = XpService::award($user, XpService::XP_COMPLETE_HABIT,
                "Completed: {$habit->name}", 'habit', $habit->id);
        }

        return response()->json([
            'is_done'     => $completion->is_done,
            'xp_result'   => $xpResult,
            'xp_progress' => XpService::progressToNextLevel($user),
        ]);
    });

    Route::get('/extension/pomodoro', function (Request $request) {
        $active = $request->user()
            ->pomodoroSessions()
            ->where('status', 'active')
            ->latest()
            ->first();

        return response()->json([
            'has_active_session' => (bool) $active,
            'session'            => $active ? [
                'id'                => $active->id,
                'work_minutes'      => $active->work_minutes,
                'break_minutes'     => $active->break_minutes,
                'started_at'        => $active->started_at->toISOString(),
                'elapsed_seconds'   => now()->diffInSeconds($active->started_at),
                'remaining_seconds' => max(0, ($active->work_minutes * 60) - now()->diffInSeconds($active->started_at)),
                'habit'             => $active->habit?->name,
                'mode'              => 'work', // Always work for an active tracked session
            ] : null,
        ]);
    });

    Route::post('/extension/pomodoro', function (Request $request) {
        $user = $request->user();
        
        $request->validate([
            'work_minutes'  => 'required|integer',
            'break_minutes' => 'required|integer',
            'sessions_completed' => 'required|integer',
            'total_minutes' => 'required|integer',
        ]);
        
        $session = \App\Models\PomodoroSession::create([
            'user_id'       => $user->id,
            'work_minutes'  => $request->work_minutes,
            'break_minutes' => $request->break_minutes,
            'sessions_completed' => $request->sessions_completed,
            'total_minutes' => $request->total_minutes,
            'started_at'    => now()->subMinutes($request->total_minutes),
            'ended_at'      => now(),
            'status'        => 'completed',
        ]);
        
        // Award XP
        $xpAmount = XpService::XP_POMODORO_SESSION * $request->sessions_completed;
        $xpResult = XpService::award(
            $user,
            $xpAmount,
            "Completed extension pomodoro session 🍅",
            'pomodoro', $session->id
        );
        
        return response()->json([
            'success'   => true,
            'xp_result' => $xpResult,
            'xp_progress' => XpService::progressToNextLevel($user->fresh()),
        ]);
    });

    // Chrome Extension — mark all notifications read
    Route::post('/extension/notifications/read-all', function (Request $request) {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['success' => true, 'unread_count' => 0]);
    });

    Route::get('/extension/jobs', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'stats'               => $user->jobSearchStats(),
            'upcoming_interviews' => $user->jobInterviews()
                ->with('application')
                ->where('scheduled_at', '>=', now())
                ->where('outcome', 'pending')
                ->orderBy('scheduled_at')
                ->take(3)
                ->get()
                ->map(fn($i) => [
                    'id'           => $i->id,
                    'type'         => $i->interview_type,
                    'company'      => $i->application->company_name,
                    'role'         => $i->application->role_title,
                    'scheduled_at' => $i->scheduled_at->toISOString(),
                    'has_prep'     => (bool) $i->ai_prep,
                ]),
        ]);
    });

    Route::post('/extension/jobs', function (Request $request) {
        $user = $request->user();
        
        $request->validate([
            'company_name' => 'required|string|max:255',
            'role_title'   => 'required|string|max:255',
            'job_url'      => 'nullable|url',
            'status'       => 'required|in:wishlist,applied,phone_screen,interview,offer,rejected,withdrawn',
            'priority'     => 'required|integer|in:1,2,3',
        ]);

        $application = $user->jobApplications()->create($request->all());

        if ($request->status === 'applied') {
            XpService::award($user, 10, "Applied to {$application->company_name} 💼", 'job_application', $application->id);
        }

        return response()->json([
            'success'     => true,
            'application' => $application,
            'xp_progress' => XpService::progressToNextLevel($user->fresh()),
        ]);
    });

});

Route::post('/extension/token', function (Request $request) {
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Delete old extension tokens
    $user->tokens()->where('name', 'chrome-extension')->delete();

    $token = $user->createToken('chrome-extension')->plainTextToken;

    return response()->json(['token' => $token]);
});
