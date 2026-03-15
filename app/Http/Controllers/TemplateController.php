<?php

namespace App\Http\Controllers;

use App\Models\HabitTemplate;
use App\Models\HabitCategory;
use App\Models\User;
use Illuminate\Http\Request;
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

        return back()->with('success', "'{$habit->name}' added to your habits! ✅");
    }

    private function matchCategory(string $categoryName, User $user): ?int
    {
        return HabitCategory::where('name', $categoryName)
            ->where(fn($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->first()?->id;
    }
}
