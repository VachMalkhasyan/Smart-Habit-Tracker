<?php

namespace Database\Seeders;

use App\Models\HabitTemplate;
use Illuminate\Database\Seeder;

class HabitTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Health & Fitness
            ['name' => 'Drink 8 glasses of water',  'description' => 'Stay hydrated throughout the day',          'category' => 'Health & Fitness', 'icon' => '💧', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 8,  'deadline_value' => 30, 'deadline_unit' => 'days',   'priority' => 1, 'sort_order' => 1],
            ['name' => 'Exercise 30 minutes',        'description' => 'Any physical activity for 30 minutes',      'category' => 'Health & Fitness', 'icon' => '🏃', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 3,  'deadline_unit' => 'months', 'priority' => 1, 'sort_order' => 2],
            ['name' => 'Sleep 8 hours',              'description' => 'Get a full night of quality sleep',         'category' => 'Health & Fitness', 'icon' => '😴', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 30, 'deadline_unit' => 'days',   'priority' => 1, 'sort_order' => 3],
            ['name' => 'Take vitamins',              'description' => 'Daily vitamin and supplement intake',       'category' => 'Health & Fitness', 'icon' => '💊', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 4],
            ['name' => 'Walk 10,000 steps',          'description' => 'Hit your daily step goal',                  'category' => 'Health & Fitness', 'icon' => '👟', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 30, 'deadline_unit' => 'days',   'priority' => 2, 'sort_order' => 5],
            ['name' => 'No junk food',               'description' => 'Avoid processed and junk food',             'category' => 'Health & Fitness', 'icon' => '🥗', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 30, 'deadline_unit' => 'days',   'priority' => 2, 'sort_order' => 6],

            // Education
            ['name' => 'Read 20 pages',              'description' => 'Read at least 20 pages of any book',        'category' => 'Education',        'icon' => '📚', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 3,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 1],
            ['name' => 'Learn a new language',       'description' => 'Practice a new language for 15 minutes',    'category' => 'Education',        'icon' => '🌍', 'goal' => 60,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 6,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 2],
            ['name' => 'Watch educational video',    'description' => 'Watch one educational or tutorial video',   'category' => 'Education',        'icon' => '🎓', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 3, 'sort_order' => 3],
            ['name' => 'Practice coding',            'description' => 'Code for at least 30 minutes',              'category' => 'Education',        'icon' => '💻', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 3,  'deadline_unit' => 'months', 'priority' => 1, 'sort_order' => 4],
            ['name' => 'Write in a journal',         'description' => 'Write your thoughts and reflections',       'category' => 'Education',        'icon' => '✍️', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 3, 'sort_order' => 5],

            // Finance
            ['name' => 'Track daily expenses',       'description' => 'Log every expense you make today',          'category' => 'Finance',          'icon' => '💰', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 1, 'sort_order' => 1],
            ['name' => 'No unnecessary spending',    'description' => 'Avoid impulse and unnecessary purchases',   'category' => 'Finance',          'icon' => '🚫', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 30, 'deadline_unit' => 'days',   'priority' => 1, 'sort_order' => 2],
            ['name' => 'Save money daily',           'description' => 'Put aside a small amount every day',        'category' => 'Finance',          'icon' => '🏦', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 3,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 3],
            ['name' => 'Review financial goals',     'description' => 'Check progress on your financial goals',    'category' => 'Finance',          'icon' => '📊', 'goal' => 4,   'goal_unit' => 'weeks',  'repeat_count' => 1,  'deadline_value' => 3,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 4],

            // Mental Health
            ['name' => 'Meditate 10 minutes',        'description' => 'Daily mindfulness meditation',              'category' => 'Mental Health',    'icon' => '🧘', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 2,  'deadline_unit' => 'months', 'priority' => 1, 'sort_order' => 1],
            ['name' => 'Gratitude journaling',       'description' => 'Write 3 things you are grateful for',      'category' => 'Mental Health',    'icon' => '🙏', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 2],
            ['name' => 'Digital detox 1 hour',       'description' => 'Spend 1 hour away from all screens',       'category' => 'Mental Health',    'icon' => '📵', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 3],
            ['name' => 'Deep breathing exercises',   'description' => '5 minutes of deep breathing exercises',    'category' => 'Mental Health',    'icon' => '🌬️', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 2,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 4],
            ['name' => 'No social media',            'description' => 'Avoid social media for the whole day',     'category' => 'Mental Health',    'icon' => '🔕', 'goal' => 14,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 14, 'deadline_unit' => 'days',   'priority' => 3, 'sort_order' => 5],

            // Personal Life
            ['name' => 'Call family or friend',      'description' => 'Connect with someone you care about',      'category' => 'Personal Life',    'icon' => '📞', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 1],
            ['name' => 'Clean and tidy room',        'description' => 'Keep your living space clean and organized','category' => 'Personal Life',    'icon' => '🧹', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 3, 'sort_order' => 2],
            ['name' => 'Cook a healthy meal',        'description' => 'Prepare a nutritious home-cooked meal',    'category' => 'Personal Life',    'icon' => '🍳', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 3],
            ['name' => 'Morning routine',            'description' => 'Complete your full morning routine',       'category' => 'Personal Life',    'icon' => '🌅', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 1, 'sort_order' => 4],
            ['name' => 'Evening routine',            'description' => 'Wind down with your evening routine',      'category' => 'Personal Life',    'icon' => '🌙', 'goal' => 30,  'goal_unit' => 'days',   'repeat_count' => 1,  'deadline_value' => 1,  'deadline_unit' => 'months', 'priority' => 2, 'sort_order' => 5],
        ];

        foreach ($templates as $template) {
            HabitTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
