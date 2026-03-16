<?php
namespace App\Http\Controllers;

use App\Models\MoodLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MoodController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Mood/Index', [
            'today_mood'    => $user->todaysMood(),
            'weekly_moods'  => $user->moodLogs()
                ->whereBetween('logged_date', [
                    now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString(),
                    now()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(),
                ])
                ->orderBy('logged_date')
                ->get(),
            'monthly_moods' => $user->moodLogs()
                ->whereMonth('logged_date', now()->month)
                ->orderBy('logged_date')
                ->get(),
            'mood_habit_correlation' => $this->getMoodHabitCorrelation($user),
            'average_this_week'      => $user->weeklyMoodAverage(),
            'streak'                 => $user->getMoodStreak(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'score' => 'required|integer|min:1|max:5',
            'note'  => 'nullable|string|max:500',
            'tags'  => 'nullable|array',
            'tags.*'=> 'string|max:30',
        ]);

        $mood = MoodLog::updateOrCreate(
            [
                'user_id'     => $request->user()->id,
                'logged_date' => today(),
            ],
            [
                'score' => $request->score,
                'emoji' => MoodLog::emojiForScore($request->score),
                'label' => MoodLog::labelForScore($request->score),
                'note'  => $request->note,
                'tags'  => $request->tags ?? [],
            ]
        );

        // Regenerate affirmation when mood is logged
        app(\App\Services\AiService::class)
            ->generateDailyAffirmation($request->user()->fresh(), forceRegenerate: true);

        return back()->with('success', 'Mood logged!');
    }

    public function today(Request $request)
    {
        return response()->json($request->user()->todaysMood());
    }

    public function history(Request $request)
    {
        return response()->json(
            $request->user()->moodLogs()->orderByDesc('logged_date')->paginate(30)
        );
    }

    private function getMoodHabitCorrelation(User $user)
    {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date      = now()->subDays($i)->toDateString();
            $mood      = $user->moodLogs()->whereDate('logged_date', $date)->first();
            $completed = $user->completions()->whereDate('completed_at', $date)->where('is_done', true)->count();
            $total     = $user->habits()->where('status', 'active')->count();

            $days->push([
                'date'            => $date,
                'mood_score'      => $mood?->score,
                'mood_emoji'      => $mood?->emoji,
                'habits_completed'=> $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ]);
        }
        return $days;
    }
}
