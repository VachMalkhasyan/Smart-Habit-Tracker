<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HabitCategory;
use App\Models\HabitTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class HabitController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $habits = $request->user()->habits()
            ->with('category')
            ->withCount('completions')
            ->orderBy('order')
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
        $template   = null;
        $templates  = HabitTemplate::orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        if ($request->has('template')) {
            $template = HabitTemplate::find($request->template);
        }

        return Inertia::render('Habits/CreateEdit', [
            'categories'      => $categories,
            'template'        => $template,
            'templateGroups'  => $templates,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'category_id'       => 'nullable|exists:habit_categories,id',
            'new_category_name' => 'nullable|string|max:255',
            'goal'              => 'required|integer|min:1',
            'goal_unit'         => 'required|in:days,weeks,months,years',
            'repeat_count'      => 'required|integer|min:1|max:20',
            'start_date'        => 'required|date',
            'deadline_value'    => 'nullable|integer|min:1',
            'deadline_unit'     => 'nullable|in:days,weeks,months,years',
            'priority'          => 'required|in:1,2,3',
            'status'            => 'in:active,inactive,completed,paused',
            'reminder_time'     => 'nullable|date_format:H:i',
        ]);

        if (empty($validated['category_id']) && !empty($validated['new_category_name'])) {
            $category = HabitCategory::create([
                'user_id' => $request->user()->id,
                'name'    => $validated['new_category_name'],
                'color'   => '#6366f1', // default indigo
                'icon'    => '✨'
            ]);
            $validated['category_id'] = $category->id;
        }

        unset($validated['new_category_name']);

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
            ->with(['cheers.user'])
            ->orderBy('completed_at', 'desc')
            ->take(30)
            ->get();

        $todayCompletion = $habit->completions()
            ->with(['cheers.user'])
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
            'deadline_value' => 'nullable|integer|min:1',
            'deadline_unit'  => 'nullable|in:days,weeks,months,years',
            'priority'       => 'required|in:1,2,3',
            'status'         => 'required|in:active,inactive,completed,paused',
            'reminder_time'  => 'nullable|date_format:H:i',
        ]);

        $habit->update($validated);

        return Redirect::route('habits.index')->with('success', 'Habit updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Habit $habit)
    {
        if ($habit->user_id !== request()->user()->id) {
            abort(403);
        }

        $habit->delete();

        return redirect()->route('habits.index')->with('success', 'Habit deleted.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:habits,id',
        ]);

        $user = $request->user();
        $orderedIds = $request->input('ordered_ids');
        
        foreach ($orderedIds as $index => $id) {
            $user->habits()->where('id', $id)->update(['priority' => $index + 1]);
        }

        return redirect()->back();
    }
}
