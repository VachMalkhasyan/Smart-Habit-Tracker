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

        $wasDone = $completion->is_done;
        
        $completion->update([
            'is_done' => !$completion->is_done,
            'count'   => !$completion->is_done ? $habit->repeat_count : 0,
        ]);

        if ($completion->is_done) {
            $xpResult = $this->awardXpIfNeeded($completion, $habit, $user, $wasFirstToday);
            $message = 'Habit completed! +' . XpService::XP_COMPLETE_HABIT . ' XP';
        } else {
            $xpResult = $this->revokeXpIfNeeded($habit, $user);
            $message = 'Habit unchecked. XP revoked.';
        }

        return back()->with([
            'success'   => $message,
            'xp_result' => $xpResult,
        ]);
    }

    public function increment(Request $request, Habit $habit)
    {
        $completion = Completion::firstOrCreate(
            ['habit_id' => $habit->id, 'user_id' => $request->user()->id, 'completed_at' => today()],
            ['count' => 0, 'is_done' => false]
        );

        $wasDone = $completion->is_done;
        $completion->count = min($completion->count + 1, $habit->repeat_count);
        $completion->is_done = $completion->count >= $habit->repeat_count;
        $completion->save();

        if ($completion->is_done && !$wasDone) {
            $today = now()->toDateString();
            $wasFirstToday = !$request->user()->completions()
                ->whereDate('completed_at', $today)
                ->where('is_done', true)
                ->where('habit_id', '!=', $habit->id)
                ->exists();

            $xpResult = $this->awardXpIfNeeded($completion, $habit, $request->user(), $wasFirstToday);

            return back()->with([
                'success'   => 'Habit completed! +' . XpService::XP_COMPLETE_HABIT . ' XP',
                'xp_result' => $xpResult,
            ]);
        }

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
            $wasDone = $completion->is_done;
            $completion->count = max($completion->count - 1, 0);
            $completion->is_done = $completion->count >= $habit->repeat_count;
            $completion->save();
            
            $this->updateStreak($habit);

            if ($wasDone && !$completion->is_done) {
                $xpResult = $this->revokeXpIfNeeded($habit, $request->user());
                return back()->with([
                    'success'   => 'Habit unchecked. XP revoked.',
                    'xp_result' => $xpResult,
                ]);
            }
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

    private function awardXpIfNeeded(Completion $completion, Habit $habit, $user, bool $wasFirstToday)
    {
        if (!$completion->is_done) return null;

        $today = now()->toDateString();
        
        $awardedLogs = \App\Models\XpLog::where('user_id', $user->id)
            ->where('source_type', 'habit')
            ->where('source_id', $habit->id)
            ->whereDate('created_at', $today)
            ->get();

        $alreadyReceivedBaseXpToday = $awardedLogs->where('amount', XpService::XP_COMPLETE_HABIT)->isNotEmpty();
        $hasAnyLogToday = $awardedLogs->isNotEmpty();

        $this->updateStreak($habit);

        if ($alreadyReceivedBaseXpToday) {
            return null; // Already fully awarded and currently checked
        }

        // Base XP for completing habit
        $xpResult = XpService::award(
            $user, XpService::XP_COMPLETE_HABIT,
            "Completed habit: {$habit->name}",
            'habit', $habit->id
        );

        // ONLY award bonuses if this is the FIRST time this habit is completed today
        if (!$hasAnyLogToday) {
            // Bonus: first habit of the day
        if ($wasFirstToday) {
            XpService::award($user, XpService::XP_FIRST_HABIT_DAY,
                'First habit of the day! 🌅');
        }

        // Bonus: streak
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
        } // End of bonus block

        return $xpResult;
    }

    private function revokeXpIfNeeded(Habit $habit, $user)
    {
        $today = now()->toDateString();

        // Check if XP was actually awarded for this habit today
        $awardedLogs = \App\Models\XpLog::where('user_id', $user->id)
            ->where('source_type', 'habit')
            ->where('source_id', $habit->id)
            ->where('amount', '>', 0)
            ->whereDate('created_at', $today)
            ->get();

        if ($awardedLogs->isEmpty()) {
            return null; // No XP was awarded, nothing to revoke
        }

        $totalRevoked = 0;
        foreach ($awardedLogs as $log) {
            $totalRevoked += $log->amount;
            XpService::revoke(
                $user, 
                $log->amount, 
                "Revoked XP for unchecking habit: {$habit->name}", 
                'habit_revoke', 
                $habit->id
            );
            // Update the log amount to 0 instead of deleting it.
            // This leaves a record that the habit was completed today at least once,
            // preventing bonuses from being farmed if it is checked again.
            $log->update(['amount' => 0]);
        }

        return ['xp_revoked' => $totalRevoked];
    }
}
