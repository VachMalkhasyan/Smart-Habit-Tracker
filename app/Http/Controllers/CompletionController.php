<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\Completion;
use Illuminate\Http\Request;

class CompletionController extends Controller
{
    public function toggle(Request $request, Habit $habit)
    {
        $completion = Completion::firstOrCreate(
            ['habit_id' => $habit->id, 'user_id' => $request->user()->id, 'completed_at' => today()],
            ['count' => 0, 'is_done' => false]
        );

        $completion->is_done = !$completion->is_done;
        if ($completion->is_done) $completion->count = $habit->repeat_count;
        $completion->save();

        $this->updateStreak($habit);

        return back();
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
