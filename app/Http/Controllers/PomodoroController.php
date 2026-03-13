<?php

namespace App\Http\Controllers;

use App\Models\PomodoroSession;
use App\Services\XpService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PomodoroController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $sessions = $user->pomodoroSessions()
            ->with('habit')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $stats = [
            'total_sessions'  => $user->pomodoroSessions()->where('status', 'completed')->sum('sessions_completed'),
            'total_minutes'   => $user->pomodoroSessions()->where('status', 'completed')->sum('total_minutes'),
            'today_sessions'  => $user->pomodoroSessions()
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('sessions_completed'),
            'this_week'       => $user->pomodoroSessions()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->where('status', 'completed')
                ->sum('sessions_completed'),
        ];

        $habits = $user->habits()->where('status', 'active')->get(['id', 'name']);

        return Inertia::render('Pomodoro/Index', [
            'sessions' => $sessions,
            'stats'    => $stats,
            'habits'   => $habits,
            'xpProgress' => XpService::progressToNextLevel($user),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'work_minutes'  => 'required|integer|min:1|max:120',
            'break_minutes' => 'required|integer|min:1|max:60',
            'habit_id'      => 'nullable|exists:habits,id',
        ]);

        $session = PomodoroSession::create([
            'user_id'       => $request->user()->id,
            'habit_id'      => $request->habit_id,
            'work_minutes'  => $request->work_minutes,
            'break_minutes' => $request->break_minutes,
            'started_at'    => now(),
            'status'        => 'active',
        ]);

        return response()->json($session);
    }

    public function complete(Request $request, PomodoroSession $session)
    {
        $request->validate([
            'sessions_completed' => 'required|integer|min:1',
            'total_minutes'      => 'required|integer|min:1',
        ]);

        $session->update([
            'sessions_completed' => $request->sessions_completed,
            'total_minutes'      => $request->total_minutes,
            'status'             => 'completed',
            'ended_at'           => now(),
        ]);

        // Award XP per session completed
        $xpAmount = XpService::XP_POMODORO_SESSION * $request->sessions_completed;
        $xpResult = XpService::award(
            $request->user(),
            $xpAmount,
            "Completed {$request->sessions_completed} pomodoro session(s) 🍅",
            'pomodoro', $session->id
        );

        return response()->json([
            'success'   => true,
            'xp_result' => $xpResult,
        ]);
    }

    public function abandon(Request $request, PomodoroSession $session)
    {
        $session->update([
            'status'   => 'abandoned',
            'ended_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
