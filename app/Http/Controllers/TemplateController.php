<?php

namespace App\Http\Controllers;

use App\Models\HabitTemplate;
use App\Models\HabitCategory;
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
}
