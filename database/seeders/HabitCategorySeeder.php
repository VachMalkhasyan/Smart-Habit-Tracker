<?php

namespace Database\Seeders;

use App\Models\HabitCategory;
use Illuminate\Database\Seeder;

class HabitCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Financial',
            'Personal Life',
            'Education',
            'Health & Fitness',
            'Mental Health',
            'Social',
        ];

        foreach ($categories as $name) {
            HabitCategory::firstOrCreate(
                ['name' => $name, 'user_id' => null]
            );
        }
    }
}
