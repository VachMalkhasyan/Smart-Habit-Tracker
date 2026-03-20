<?php

namespace App\Http\Controllers;

use App\Models\HabitTemplate;
use App\Models\HabitCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = HabitTemplate::orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return Inertia::render('Templates/Index', [
            'templateGroups' => $templates,
        ]);
    }

    public function show(HabitTemplate $template)
    {
        return response()->json($template);
    }

    public function quickAdd(Request $request, HabitTemplate $template)
    {
        $habit = $request->user()->habits()->create([
            'name'          => $template->name,
            'category_id'   => $this->matchCategory($template->category, $request->user()),
            'goal'          => $template->goal,
            'goal_unit'     => $template->goal_unit,
            'repeat_count'  => $template->repeat_count,
            'deadline_value'=> $template->deadline_value,
            'deadline_unit' => $template->deadline_unit,
            'priority'      => $template->priority,
            'status'        => 'active',
            'start_date'    => today(),
        ]);

        return Redirect::route('habits.index')->with('success', 'Habit added! ✨');
    }


    private function matchCategory(string $categoryName, User $user): ?int
    {
        $category = HabitCategory::where('name', $categoryName)
            ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->first();

        if ($category) {
            return $category->id;
        }

        // Auto-create category if missing
        $newCategory = HabitCategory::create([
            'user_id' => $user->id,
            'name'    => $categoryName,
            'color'   => '#6366f1', // Default indigo
            'icon'    => 'Sparkles',
        ]);

        return $newCategory->id;
    }
}
