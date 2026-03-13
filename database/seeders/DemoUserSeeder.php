<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Habit;
use App\Models\HabitCategory;
use App\Models\Completion;
use App\Models\Friendship;
use App\Models\Cheer;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DemoUserSeeder extends Seeder
{
    public function run()
    {
        $categories = HabitCategory::all();

        // 1. Create Users
        $usersData = [
            ['name' => 'Alice Johnson', 'email' => 'alice@demo.com', 'username' => 'alice_j', 'bio' => 'Health enthusiast'],
            ['name' => 'Bob Smith', 'email' => 'bob@demo.com', 'username' => 'bob_smith', 'bio' => 'Lifelong learner'],
            ['name' => 'Charlie Davis', 'email' => 'charlie@demo.com', 'username' => 'charlie_d', 'bio' => 'Data driven'],
            ['name' => 'Diana Prince', 'email' => 'diana@demo.com', 'username' => 'diana_p', 'bio' => 'Living my best life'],
            ['name' => 'Evan Wright', 'email' => 'evan@demo.com', 'username' => 'evan_w', 'bio' => 'Just here for fun'],
        ];

        $users = [];
        foreach ($usersData as $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email']
            ], [
                'name' => $userData['name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            
            // Set additional settings json if it exists
            $settings = $user->settings ?? [];
            $settings['username'] = $userData['username'];
            $settings['bio'] = $userData['bio'];
            $settings['is_public'] = true;
            $user->settings = $settings;
            $user->save();

            $users[] = $user;
        }

        // 2. Generate Habits & Completions
        foreach ($users as $user) {
            $this->seedHabitsForUser($user, $categories);
        }
        
        // 3. Create Friendships between users
        $this->seedFriendships(collect($users));
        
        // 4. Generate some cheers
        $this->seedCheers(collect($users));
    }

    private function seedHabitsForUser($user, $categories)
    {
        $habitTemplates = [
            ['name' => 'Drink Water', 'description' => 'Drink 8 glasses of water', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 2],
            ['name' => 'Read a Book', 'description' => 'Read 10 pages daily', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 3],
            ['name' => 'Workout', 'description' => 'Go to the gym or run', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 1],
            ['name' => 'Meditate', 'description' => '10 mins of mindfulness', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 2],
            ['name' => 'Sleep Early', 'description' => 'In bed by 11 PM', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 2],
            ['name' => 'Journal', 'description' => 'Write thoughts of the day', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 3],
            ['name' => 'No Sugar', 'description' => 'Avoid sugary drinks and snacks', 'goal' => 30, 'goal_unit' => 'days', 'repeat_count' => 1, 'priority' => 1],
        ];

        // Randomly pick 3-6 habits for this user
        $userTemplateCount = rand(3, 6);
        $userTemplates = collect($habitTemplates)->random($userTemplateCount);

        foreach ($userTemplates as $template) {
            $category = $categories->random();

            $habit = Habit::create([
                'user_id' => $user->id,
                'category_id' => $category ? $category->id : null,
                'name' => $template['name'],
                'description' => $template['description'],
                'goal' => $template['goal'],
                'goal_unit' => $template['goal_unit'],
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(rand(45, 90)),
                'deadline_value' => rand(1, 4),
                'deadline_unit' => 'months',
                'repeat_count' => $template['repeat_count'],
                'priority' => $template['priority'],
                'current_streak' => 0,
                'longest_streak' => 0,
            ]);

            $this->seedCompletionsForHabit($user, $habit);
        }
    }

    private function seedCompletionsForHabit($user, $habit)
    {
        $currentStreak = 0;
        $longestStreak = 0;
        
        $startDate = $habit->start_date;
        $today = Carbon::today();
        
        $currentDate = $startDate->copy();
        
        // Have a baseline success rate per habit for consistency 
        // e.g. someone is really good at working out (85%), but bad at meditating (40%)
        $habitSuccessRate = rand(50, 95); 
        
        while ($currentDate->lte($today)) {
            if (rand(1, 100) <= $habitSuccessRate) {
                $completion = Completion::create([
                    'habit_id' => $habit->id,
                    'user_id' => $user->id,
                    'completed_at' => $currentDate,
                    'count' => $habit->repeat_count,
                    'is_done' => true,
                ]);
                $currentStreak++;
                if ($currentStreak > $longestStreak) {
                    $longestStreak = $currentStreak;
                }
                
                // Add XP for completion
                $xpAmount = 10;
                // Bonus xp for streak
                if ($currentStreak % 7 == 0) $xpAmount += 20;
                
                \App\Models\XpLog::create([
                    'user_id' => $user->id,
                    'amount' => $xpAmount,
                    'reason' => 'Habit completed',
                    'source_type' => 'App\Models\Completion',
                    'source_id' => $completion->id,
                ]);
                
                $user->increment('xp', $xpAmount);
                
            } else {
                $currentStreak = 0; // Missed day resets streak
            }
            
            $currentDate->addDay();
        }

        // Update levels roughly based on total XP (every 100 is a level)
        $user->update([
            'level' => floor($user->xp / 100) + 1
        ]);

        // Update habit with calculated streaks
        $habit->update([
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
        ]);
    }
    
    private function seedFriendships($users)
    {
        // Give everyone a few friends
        foreach ($users as $user) {
            $possibleFriends = $users->where('id', '!=', $user->id)->random(rand(2, 4));
            
            foreach ($possibleFriends as $friend) {
                // Check if friendship already exists
                $exists = Friendship::where(function($q) use ($user, $friend) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $friend->id);
                })->orWhere(function($q) use ($user, $friend) {
                    $q->where('sender_id', $friend->id)->where('receiver_id', $user->id);
                })->exists();
                
                if (!$exists) {
                    Friendship::create([
                        'sender_id' => $user->id,
                        'receiver_id' => $friend->id,
                        'status' => 'accepted' // assume they accepted
                    ]);
                }
            }
        }
    }
    
    private function seedCheers($users)
    {
        $emojis = ['🔥', '👏', '💪', '⭐', '🚀'];
        
        // Randomly grab some recent completions to add cheers to
        $recentCompletions = Completion::where('completed_at', '>=', Carbon::now()->subDays(7))
            ->inRandomOrder()
            ->limit(50)
            ->get();
            
        foreach ($recentCompletions as $completion) {
            // Find a friend of the completions user
            $friendships = Friendship::where('status', 'accepted')
                ->where(function($q) use ($completion) {
                    $q->where('sender_id', $completion->user_id)
                      ->orWhere('receiver_id', $completion->user_id);
                })->get();
                
            if ($friendships->isEmpty()) continue;
            
            // Generate 1-3 cheers per chosen completion
            $numCheers = rand(1, 3);
            if ($friendships->count() < $numCheers) {
                $numCheers = $friendships->count();
            }
            
            $friends = $friendships->random($numCheers);
            
            foreach ($friends as $friendship) {
                $friendId = $friendship->sender_id === $completion->user_id 
                    ? $friendship->receiver_id 
                    : $friendship->sender_id;
                    
                Cheer::create([
                    'user_id' => $friendId,
                    'completion_id' => $completion->id,
                    'emoji' => $emojis[array_rand($emojis)],
                ]);
            }
        }
    }
}
