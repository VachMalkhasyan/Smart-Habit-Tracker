<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HabitCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $categories = HabitCategory::availableFor($request->user()->id)->get();

        return Inertia::render('Onboarding/Index', [
            'categories' => $categories,
        ]);
    }

    public function complete(Request $request)
    {
        $request->validate([
            'username'        => 'nullable|string|max:30|alpha_dash|unique:users,username,' . $request->user()->id,
            'bio'             => 'nullable|string|max:160',
            'interests'       => 'nullable|array',
            'reminder_time'   => 'nullable|string|max:5',
            'habit_name'      => 'nullable|string|max:255',
            'habit_category'  => 'nullable|integer|exists:habit_categories,id',
            'habit_goal'      => 'nullable|integer|min:1',
            'habit_goal_unit' => 'nullable|string|in:days,weeks,months,years',
        ]);

        $user = $request->user();

        // Update profile
        $user->update([
            'username'             => $request->username,
            'bio'                  => $request->bio,
            'onboarding_completed' => true,
            'settings'             => array_merge($user->settings ?? [], [
                'email_reminders' => true,
                'reminder_time'   => $request->reminder_time ?? '08:00',
            ]),
        ]);

        // Create first habit if provided
        if ($request->filled('habit_name')) {
            Habit::create([
                'user_id'        => $user->id,
                'name'           => $request->habit_name,
                'category_id'    => $request->habit_category,
                'goal'           => $request->habit_goal ?? 30,
                'goal_unit'      => $request->habit_goal_unit ?? 'days',
                'repeat_count'   => 1,
                'start_date'     => now()->toDateString(),
                'deadline_value' => 30,
                'deadline_unit'  => 'days',
                'priority'       => 2,
                'status'         => 'active',
            ]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to Smart Habit Tracker! 🎉');
    }

    public function skip(Request $request)
    {
        $request->user()->update(['onboarding_completed' => true]);
        return redirect()->route('dashboard');
    }

    public function reset(Request $request)
    {
        $request->user()->update(['onboarding_completed' => false]);
        return redirect()->route('onboarding');
    }
}
