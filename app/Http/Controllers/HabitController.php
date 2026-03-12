<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HabitCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class HabitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $habits = $request->user()->habits()
            ->with('category')
            ->withCount('completions')
            ->orderBy('priority')
            ->get();

        $categories = HabitCategory::availableFor($request->user()->id)->get();

        return Inertia::render('Habits/Index', [
            'habits'     => $habits,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $categories = HabitCategory::availableFor($request->user()->id)->get();
        return Inertia::render('Habits/CreateEdit', ['categories' => $categories]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'category_id'    => 'nullable|exists:habit_categories,id',
            'goal'           => 'required|integer|min:1',
            'goal_unit'      => 'required|in:days,weeks,months,years',
            'repeat_count'   => 'required|integer|min:1|max:20',
            'start_date'     => 'required|date',
            'deadline_value' => 'required|integer|min:1',
            'deadline_unit'  => 'required|in:days,weeks,months,years',
            'priority'       => 'required|in:1,2,3',
            'status'         => 'in:active,inactive,completed,paused',
        ]);

        $request->user()->habits()->create($validated);

        return Redirect::route('habits.index')->with('success', 'Habit created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Habit $habit)
    {
        $this->authorize('update', $habit);

        $completions = $habit->completions()
            ->orderBy('completed_at', 'desc')
            ->take(30)
            ->get();

        $todayCompletion = $habit->completions()
            ->whereDate('completed_at', today())
            ->first();

        return Inertia::render('Habits/Show', [
            'habit'           => $habit->load('category'),
            'completions'     => $completions,
            'todayCompletion' => $todayCompletion,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Habit $habit)
    {
        $this->authorize('update', $habit);
        $categories = HabitCategory::availableFor($request->user()->id)->get();
        return Inertia::render('Habits/CreateEdit', [
            'habit'      => $habit,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Habit $habit)
    {
        $this->authorize('update', $habit);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'category_id'    => 'nullable|exists:habit_categories,id',
            'goal'           => 'required|integer|min:1',
            'goal_unit'      => 'required|in:days,weeks,months,years',
            'repeat_count'   => 'required|integer|min:1|max:20',
            'start_date'     => 'required|date',
            'deadline_value' => 'required|integer|min:1',
            'deadline_unit'  => 'required|in:days,weeks,months,years',
            'priority'       => 'required|in:1,2,3',
            'status'         => 'required|in:active,inactive,completed,paused',
        ]);

        $habit->update($validated);

        return Redirect::route('habits.index')->with('success', 'Habit updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Habit $habit)
    {
        $this->authorize('delete', $habit);
        $habit->delete();
        return Redirect::route('habits.index')->with('success', 'Habit deleted!');
    }
}
