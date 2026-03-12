<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Settings/Index', [
            'settings' => $request->user()->settings ?? [],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_reminders'     => 'boolean',
            'missed_habit_alerts' => 'boolean',
            'weekly_summary'      => 'boolean',
            'theme'               => 'in:light,dark,system',
            'week_start'          => 'in:monday,sunday,saturday',
            'default_priority'    => 'in:1,2,3',
            'default_goal_unit'   => 'in:days,weeks,months,years',
        ]);

        $request->user()->update(['settings' => $validated]);

        return back()->with('success', 'Settings saved!');
    }

    public function deleteHabits(Request $request)
    {
        $request->user()->habits()->forceDelete();
        return back()->with('success', 'All habits deleted!');
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        auth()->logout();
        $user->delete();
        return redirect('/')->with('success', 'Account deleted!');
    }
}
