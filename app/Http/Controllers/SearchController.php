<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q    = $request->get('q', '');
        $user = $request->user();

        if (!$q) {
            return response()->json(['habits' => [], 'categories' => []]);
        }

        $habits = $user->habits()
            ->with('category')
            ->where('name', 'like', "%{$q}%")
            ->orWhere('description', 'like', "%{$q}%")
            ->limit(6)
            ->get();

        $categories = \App\Models\HabitCategory::availableFor($user->id)
            ->withCount('habits')
            ->where('name', 'like', "%{$q}%")
            ->limit(4)
            ->get();

        return response()->json([
            'habits'     => $habits,
            'categories' => $categories,
        ]);
    }
}
