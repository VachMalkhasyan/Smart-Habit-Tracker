<?php

namespace App\Http\Controllers;

use App\Events\FriendActivityUpdated;
use App\Events\HabitCompleted;
use App\Events\XpAwarded;
use App\Models\Habit;
use App\Models\Completion;
use App\Notifications\AllHabitsDoneToday;
use App\Services\XpService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompletionController extends Controller
{
    public function toggle(Request $request, Habit $habit)
    {
        $today = now()->toDateString();
        $user = $request->user();
        // Find existing completion or prepare for creation
        $completion = $habit->completions()->whereDate('completed_at', $today)->first();

        $wasDone = $completion ? $completion->is_done : false;

        if ($completion) {
            // Toggle existing
            $completion->update([
                'is_done' => !$completion->is_done,
                'count'   => !$completion->is_done ? $habit->repeat_count : 0,
            ]);
        } else {
            // Create new for today
            $completion = Completion::create([
                'habit_id'     => $habit->id,
                'user_id'      => $user->id,
                'completed_at' => today(),
                'is_done'      => true,
                'count'        => $habit->repeat_count === 1 ? 1 : $habit->repeat_count, // Toggling usually means "done"
            ]);
        }

        $wasFirstToday = !$user->completions()
            ->whereDate('completed_at', $today)
            ->where('is_done', true)
            ->where('habit_id', '!=', $habit->id)
            ->exists();

        $alreadyAwarded = \App\Models\XpLog::where('user_id', $user->id)
            ->where('source_type', 'habit')
            ->where('source_id', $habit->id)
            ->whereDate('created_at', today())
            ->where('amount', '>', 0)
            ->exists();

        if ($wasDone !== $completion->is_done) {
            if ($completion->is_done && !$alreadyAwarded) {
                $xpResult = $this->awardXpIfNeeded($completion, $habit, $user, $wasFirstToday);
                $message = 'Habit completed! +' . XpService::XP_COMPLETE_HABIT . ' XP';
            } elseif ($completion->is_done && $alreadyAwarded) {
                $xpResult = null; // already awarded today, skip silently
                $message = 'Habit checked.';
            } else {
                $xpResult = $this->revokeXpIfNeeded($habit, $user);
                $message = 'Habit unchecked. XP revoked.';
            }

            broadcast(new HabitCompleted(
                $user,
                $habit,
                $completion->is_done,
                $habit->fresh()->current_streak,
            ))->toOthers();

            if ($completion->is_done) {
                if ($xpResult && isset($xpResult['xp_awarded'])) {
                    broadcast(new XpAwarded(
                        $user->fresh(),
                        $xpResult['xp_awarded'],
                        "Completed: {$habit->name}",
                        $xpResult['leveled_up'] ?? false,
                        $xpResult['new_level'] ?? $user->level,
                    ));
                }
            } else {
                if ($xpResult && isset($xpResult['xp_revoked']) && $xpResult['xp_revoked'] > 0) {
                    broadcast(new XpAwarded(
                        $user->fresh(),
                        -$xpResult['xp_revoked'],
                        "Unchecked: {$habit->name}",
                        false,
                        $user->fresh()->level,
                    ));
                }
            }

            if ($completion->is_done) {
                broadcast(new FriendActivityUpdated($user, $habit, true));
            }
            
            // Clear weekly summary cache at end of each day
            // If we've made completion toggle changes, check if summary is stale
            if ($user->last_weekly_summary_date?->isToday()) {
                // Keep today's summary but mark it as stale after 6 hours
                $summaryAge = now()->diffInHours($user->updated_at);
                if ($summaryAge > 6) {
                    $user->update(['last_weekly_summary_date' => null]);
                }
            }
        }

        return back()->with([
            'success' => $message ?? 'State toggled.',
            'xp_result' => $xpResult ?? null,
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

            $alreadyAwardedCount = \App\Models\XpLog::where('user_id', $request->user()->id)
                ->where('source_type', 'habit')
                ->where('source_id', $habit->id)
                ->whereDate('created_at', today())
                ->where('amount', '>', 0)
                ->exists();

            if (!$alreadyAwardedCount) {
                $xpResult = $this->awardXpIfNeeded($completion, $habit, $request->user(), $wasFirstToday);

                return back()->with([
                    'success' => 'Habit completed! +' . XpService::XP_COMPLETE_HABIT . ' XP',
                    'xp_result' => $xpResult,
                ]);
            } else {
                return back()->with([
                    'success' => 'Habit marked fully done.',
                ]);
            }
        }

        $this->updateStreak($habit);

        return back();
    }

    public function decrement(Request $request, Habit $habit)
    {
        $completion = Completion::where([
            'habit_id' => $habit->id,
            'user_id' => $request->user()->id,
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
                    'success' => 'Habit unchecked. XP revoked.',
                    'xp_result' => $xpResult,
                ]);
            }
        }

        return back();
    }

    private function updateStreak(Habit $habit)
    {
        $dates = Completion::where('habit_id', $habit->id)
            ->where('is_done', true)
            ->pluck('completed_at')
            ->map(fn($d) => $d->toDateString())
            ->flip();

        $streak = 0;
        $date = today()->toDateString();

        while (isset($dates[$date])) {
            $streak++;
            $date = Carbon::parse($date)->subDay()->toDateString();
        }

        $habit->update([
            'current_streak' => $streak,
            'longest_streak' => max($habit->longest_streak, $streak),
        ]);
    }

    private function awardXpIfNeeded(Completion $completion, Habit $habit, $user, bool $wasFirstToday)
    {
        if (!$completion->is_done)
            return null;

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
            return null;
        }

        $xpResult = XpService::award(
            $user, XpService::XP_COMPLETE_HABIT,
            "Completed habit: {$habit->name}",
            'habit', $habit->id
        );

        if (!$hasAnyLogToday) {
            if ($wasFirstToday) {
                XpService::award($user, XpService::XP_FIRST_HABIT_DAY,
                    'First habit of the day! 🌅');
            }

            if ($habit->fresh()->current_streak > 0 && $habit->fresh()->current_streak % 7 === 0) {
                $streakBonus = $habit->current_streak * XpService::XP_STREAK_BONUS;
                XpService::award($user, $streakBonus,
                    "🔥 {$habit->current_streak}-day streak on {$habit->name}!");
            }

            $totalActive = $user->habits()->where('status', 'active')->count();
            $doneToday = $user->completions()
                ->whereDate('completed_at', $today)
                ->where('is_done', true)
                ->count();

            if ($totalActive > 0 && $doneToday >= $totalActive) {
                XpService::award($user, XpService::XP_ALL_HABITS_DONE,
                    '🎯 All habits completed today!');

                // Send AllHabitsDoneToday notification (only once — check it hasn't been sent today)
                $alreadyNotified = $user->notifications()
                    ->where('type', \App\Notifications\AllHabitsDoneToday::class)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (!$alreadyNotified) {
                    $user->notify(new AllHabitsDoneToday($totalActive));
                }
            }
        }

        return $xpResult;
    }

    private function revokeXpIfNeeded(Habit $habit, $user)
    {
        $today = now()->toDateString();

        $awardedLogs = \App\Models\XpLog::where('user_id', $user->id)
            ->where('source_type', 'habit')
            ->where('source_id', $habit->id)
            ->where('amount', '>', 0)
            ->whereDate('created_at', $today)
            ->get();

        if ($awardedLogs->isEmpty()) {
            return null;
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
            $log->update(['amount' => 0]);
        }

        return ['xp_revoked' => $totalRevoked];
    }
}