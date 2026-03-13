<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\Completion;
use App\Services\XpService;
use Illuminate\Http\Request;

class CompletionController extends Controller
{
    public function toggle(Request $request, Habit $habit)
    {
        $today      = now()->toDateString();
        $user       = $request->user();
        $completion = $habit->completions()->firstOrCreate(
            ['completed_at' => $today],
            ['user_id' => $user->id, 'count' => 0, 'is_done' => false]
        );

        $wasFirstToday = !$user->completions()
            ->whereDate('completed_at', $today)
            ->where('is_done', true)
            ->where('habit_id', '!=', $habit->id)
            ->exists();

        $completion->update([
            'is_done' => !$completion->is_done,
            'count'   => !$completion->is_done ? $habit->repeat_count : 0,
        ]);

        $xpResult = null;

        if ($completion->is_done) {
            // Base XP for completing habit
            $xpResult = XpService::award(
                $user, XpService::XP_COMPLETE_HABIT,
                "Completed habit: {$habit->name}",
                'habit', $habit->id
            );

            // Bonus: first habit of the day
            if ($wasFirstToday) {
                XpService::award($user, XpService::XP_FIRST_HABIT_DAY,
                    'First habit of the day! 🌅');
            }

            // Bonus: streak
            $this->updateStreak($habit);
            if ($habit->fresh()->current_streak > 0 && $habit->fresh()->current_streak % 7 === 0) {
                $streakBonus = $habit->current_streak * XpService::XP_STREAK_BONUS;
                XpService::award($user, $streakBonus,
                    "🔥 {$habit->current_streak}-day streak on {$habit->name}!");
            }

            // Bonus: all habits done today
            $totalActive = $user->habits()->where('status', 'active')->count();
            $doneToday   = $user->completions()
                ->whereDate('completed_at', $today)
                ->where('is_done', true)
                ->count();

            if ($totalActive > 0 && $doneToday >= $totalActive) {
                XpService::award($user, XpService::XP_ALL_HABITS_DONE,
                    '🎯 All habits completed today!');
            }
        }

        return back()->with([
            'success'   => $completion->is_done ? 'Habit completed! +' . XpService::XP_COMPLETE_HABIT . ' XP' : 'Habit unchecked',
            'xp_result' => $xpResult,
        ]);
    }

    public function increment(Request $request, Habit $habit)
    {
        $completion = Completion::firstOrCreate(
            ['habit_id' => $habit->id, 'user_id' => $request->user()->id, 'completed_at' => today()],
            ['count' => 0, 'is_done' => false]
        );

        $completion->count = min($completion->count + 1, $habit->repeat_count);
        $completion->is_done = $completion->count >= $habit->repeat_count;
        $completion->save();

        $this->updateStreak($habit);

        return back();
    }

    public function decrement(Request $request, Habit $habit)
    {
        $completion = Completion::where([
            'habit_id'     => $habit->id,
            'user_id'      => $request->user()->id,
            'completed_at' => today(),
        ])->first();

        if ($completion) {
            $completion->count = max($completion->count - 1, 0);
            $completion->is_done = $completion->count >= $habit->repeat_count;
            $completion->save();
        }

        return back();
    }

    private function updateStreak(Habit $habit)
    {
        // Recalculate current streak
        $streak = 0;
        $date = today();

        while (true) {
            $exists = Completion::where('habit_id', $habit->id)
                ->whereDate('completed_at', $date)
                ->where('is_done', true)
                ->exists();

            if (!$exists) break;

            $streak++;
            $date = $date->subDay();
        }

        $habit->current_streak = $streak;
        $habit->longest_streak = max($habit->longest_streak, $streak);
        $habit->save();
    }
}
