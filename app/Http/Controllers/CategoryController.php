<?php

namespace App\Http\Controllers;

use App\Models\HabitCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = HabitCategory::availableFor($request->user()->id)
            ->withCount('habits')
            ->orderByRaw('user_id IS NOT NULL')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:habit_categories,name',
        ]);

        $request->user()->habitCategories()->create([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Category created!');
    }

    public function update(Request $request, HabitCategory $category)
    {
        $this->authorize('update', $category);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update(['name' => $request->name]);

        return back()->with('success', 'Category updated!');
    }

    public function destroy(HabitCategory $category)
    {
        $this->authorize('delete', $category);
        $category->delete();
        return back()->with('success', 'Category deleted!');
    }
}
