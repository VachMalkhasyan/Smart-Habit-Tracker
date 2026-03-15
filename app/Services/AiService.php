<?php

// ============================================
// AI SERVICE
// Current mode: DEVELOPMENT (Groq - free)
// To switch to production (Claude Haiku):
//   1. Add ANTHROPIC_API_KEY to .env
//   2. In each method, comment out the Groq block
//   3. Uncomment the Claude Haiku block
//   4. Update the comment at top of this file
// ============================================

namespace App\Services;

use App\Models\AiConversation;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    /**
     * Builds the full personalized system prompt. Pulls live data from the user's relationships.
     */
    public function buildSystemPrompt(User $user): string
    {
        $activeHabits = $user->habits()->where('status', 'active')->get();
        $habitList = $activeHabits->map(function ($h) {
            $catName = $h->category ? $h->category->name : 'None';
            return "- {$h->name} | Streak: {$h->current_streak} days | Priority: {$h->priority} | Category: {$catName}";
        })->implode("\n");

        $longestStreak = $user->habits()->max('longest_streak') ?: 0;
        $habitsCompletedToday = $activeHabits->filter(fn($h) => $h->completed_today)->count();
        $totalActive = $activeHabits->count();

        // Calculate a rough completion rate this week for context
        // This is a placeholder as precise analytic week calculations can be heavy, but we'll use a basic metric
        // For now, we'll keep the completion rate and weak days as placeholders for future expansion

            
        // Assuming completions relationship exists or just fake it if we don't have the full model in memory
        $weakDays = "Monday: 40% completion, Sunday: 20% completion"; // Mocked based on user prompt example

        $moodLog = "Not yet tracking mood"; // Placeholder as mood logging isn't fully implemented yet
        $recentDiary = "No diary entries yet"; // Placeholder

        $goals = $user->settings['goals'] ?? 'None explicitly set';

        return "You are a personal habit coach and life assistant for {$user->name}.
Today is " . now()->format('l, F j, Y') . ".

THEIR PROFILE:
- Level: {$user->level}, Total XP: {$user->xp}
- Member since: {$user->created_at->format('M Y')}

THEIR ACTIVE HABITS ({$totalActive} total):
{$habitList}

THEIR STREAK STATS:
- Longest streak overall: {$longestStreak} days
- Habits completed today: {$habitsCompletedToday} of {$totalActive}

THEIR GOALS (from onboarding):
{$goals}

MOOD THIS WEEK:
{$moodLog}

RECENT DIARY:
{$recentDiary}

INSTRUCTIONS:
- Be warm, encouraging, and specific — never generic
- Always reference their actual habit names and streak numbers
- Keep responses concise (3-5 sentences max unless they ask for detail)
- If they ask for a habit suggestion, consider what they already track
- Never make up data — only reference what is provided above";
    }

    /**
     * Main method. Saves user message, checks summary, sends to Claude, saves response.
     */
    public function chat(User $user, AiConversation $conversation, string $userMessage): string
    {
        // 1. Save user message
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessage,
        ]);

        // 2. Check if conversation has 8+ messages -> summarize first
        if ($conversation->messages()->count() >= 8) {
            $this->summarize($conversation);
        }

        // 3. Build messages array
        $messages = [];

        if ($conversation->summary) {
            $messages[] = [
                'role' => 'user',
                'content' => 'Previous conversation summary: ' . $conversation->summary
            ];
            $messages[] = [
                'role' => 'assistant',
                'content' => 'Understood, I have context from our previous conversation.'
            ];
        }

        foreach ($conversation->recentMessages as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        // 4. Call API
        // ============================================
        // PRODUCTION: Claude Haiku (Anthropic)
        // Uncomment this and comment out Groq block when going live
        // ============================================
        // $response = Http::withHeaders([
        //     'x-api-key'         => config('services.anthropic.key'),
        //     'anthropic-version' => '2023-06-01',
        //     'content-type'      => 'application/json',
        // ])->post('https://api.anthropic.com/v1/messages', [
        //     'model'      => 'claude-haiku-4-5-20251001',
        //     'max_tokens' => 1024,
        //     'system'     => $this->buildSystemPrompt($user),
        //     'messages'   => $messages,
        // ]);
        // 
        // if (!$response->successful()) {
        //     \Log::error('Anthropic API Error', ['response' => $response->json()]);
        //     return "I'm having trouble connecting to my brain right now. Please try again later.";
        // }
        // 
        // $content     = $response->json('content.0.text');
        // $tokensUsed  = $response->json('usage.input_tokens', 0) + $response->json('usage.output_tokens', 0);
        // ============================================

        // ============================================
        // DEVELOPMENT: Groq (free tier)
        // Comment this out and uncomment Anthropic block for production
        // ============================================
        $groqMessages = $messages; // Groq uses same format as OpenAI

        // Groq doesn't have a separate system field — prepend as system message
        array_unshift($groqMessages, [
            'role'    => 'system',
            'content' => $this->buildSystemPrompt($user),
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 1024,
            'messages'   => $groqMessages,
        ]);

        if (!$response->successful()) {
            \Log::error('Groq API Error', ['response' => $response->json()]);
            return "I'm having trouble connecting to my brain right now. Please try again later.";
        }

        $content    = $response->json('choices.0.message.content');
        $tokensUsed = $response->json('usage.prompt_tokens', 0) + $response->json('usage.completion_tokens', 0);
        // ============================================

        // 5. Save assistant response
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'tokens_used' => $tokensUsed,
        ]);

        // 6. Increment counts and reward XP
        $conversation->increment('tokens_used', $tokensUsed);
        
        $user->increment('xp', 2);
        // We could also call XpService->addXp but we'll do raw increment for simple engagement

        return $content;
    }

    /**
     * Summarize old messages to save context space. Triggered when message count >= 8
     */
    public function summarize(AiConversation $conversation): void
    {
        // Get all messages older than the last 4
        $messagesToSummarize = $conversation->messages()->oldest()->skip(0)->take($conversation->messages()->count() - 4)->get();
        
        if ($messagesToSummarize->isEmpty()) return;

        $formattedLog = $messagesToSummarize->map(fn($m) => ucfirst($m->role) . ': ' . $m->content)->implode("\n\n");

        // ============================================
        // PRODUCTION: Claude Haiku (Anthropic)
        // ============================================
        // $response = Http::withHeaders([
        //     'x-api-key'         => config('services.anthropic.key'),
        //     'anthropic-version' => '2023-06-01',
        //     'content-type'      => 'application/json',
        // ])->post('https://api.anthropic.com/v1/messages', [
        //     'model'      => 'claude-haiku-4-5-20251001',
        //     'max_tokens' => 500,
        //     'messages'   => [
        //         [
        //             'role'    => 'user',
        //             'content' => "Summarize this conversation in 4-5 sentences. Keep:\n- Key questions the user asked\n- Advice that was given\n- Any habits or goals mentioned\n- User's current concerns or focus areas\n\nConversation:\n" . $formattedLog
        //         ]
        //     ],
        // ]);
        // 
        // if ($response->successful()) {
        //     $summary = $response->json('content.0.text');
        //     $tokensUsed = $response->json('usage.input_tokens', 0) + $response->json('usage.output_tokens', 0);
        // ============================================

        // ============================================
        // DEVELOPMENT: Groq (free tier)
        // ============================================
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 500,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => "Summarize this conversation in 4-5 sentences. Keep:\n- Key questions the user asked\n- Advice that was given\n- Any habits or goals mentioned\n- User's current concerns or focus areas\n\nConversation:\n" . $formattedLog
                ]
            ],
        ]);

        if ($response->successful()) {
            $summary    = $response->json('choices.0.message.content');
            $tokensUsed = $response->json('usage.prompt_tokens', 0) + $response->json('usage.completion_tokens', 0);
        // ============================================
            
            // Combine with existing summary if it exists
            if ($conversation->summary) {
                // Summarize the summary + new messages to keep it dense, but for simplicity:
                $summary = $conversation->summary . "\n" . $summary; 
            }

            $conversation->update(['summary' => $summary]);
            $conversation->increment('tokens_used', $tokensUsed);

            // Delete summarized messages
            $conversation->messages()->whereIn('id', $messagesToSummarize->pluck('id'))->delete();
        }
    }

    /**
     * Generate daily affirmation at midnight
     */
    public function generateDailyAffirmation(User $user): string
    {
        if ($user->affirmation_date === now()->toDateString()) {
            return $user->daily_affirmation ?? '';
        }

        $habitsCount = $user->habits()->where('status', 'active')->count();

        // ============================================
        // PRODUCTION: Claude Haiku (Anthropic)
        // ============================================
        // $response = Http::withHeaders([
        //     'x-api-key'         => config('services.anthropic.key'),
        //     'anthropic-version' => '2023-06-01',
        //     'content-type'      => 'application/json',
        // ])->post('https://api.anthropic.com/v1/messages', [
        //     'model'      => 'claude-haiku-4-5-20251001',
        //     'max_tokens' => 150,
        //     'system'     => "You are a personal habit coach. They currently have {$habitsCount} active habits. Speak directly to them.",
        //     'messages'   => [
        //         [
        //             'role'    => 'user',
        //             'content' => "Give me a deeply encouraging, poetic, but completely grounded daily affirmation. 2 sentences maximum."
        //         ]
        //     ],
        // ]);
        // $affirmation = $response->successful() ? $response->json('content.0.text') : tap('You have the power to shape your days. One habit at a time, you are building the life you want.');
        // ============================================

        // ============================================
        // DEVELOPMENT: Groq (free tier)
        // ============================================
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 150,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => "You are a personal habit coach. They currently have {$habitsCount} active habits. Speak directly to them."
                ],
                [
                    'role'    => 'user',
                    'content' => "Give me a deeply encouraging, poetic, but completely grounded daily affirmation. 2 sentences maximum."
                ]
            ],
        ]);
        $affirmation = $response->successful() ? $response->json('choices.0.message.content') : 'You have the power to shape your days. One habit at a time, you are building the life you want.';
        // ============================================

        $user->update([
            'daily_affirmation' => $affirmation,
            'affirmation_date' => now()->toDateString(),
        ]);

        return $affirmation;
    }

    /**
     * Suggest 5 personalized habits
     */
    public function suggestHabits(User $user): array
    {
        $activeHabits = $user->habits()->pluck('name')->implode(', ');
        
        $prompt = "The user currently tracks these habits: {$activeHabits}.
Suggest 5 new habits they might enjoy checking off daily.
Respond ONLY with a valid JSON array matching this exact schema, do not include any markdown wrappers or extra text:
[
  {
    \"name\": \"Habit Name\",
    \"reason\": \"Why this makes sense for them\",
    \"category\": \"Category Name\",
    \"goal\": 30,
    \"goal_unit\": \"days\",
    \"priority\": 2
  }
]";

        // ============================================
        // PRODUCTION: Claude Haiku (Anthropic)
        // ============================================
        // $response = Http::withHeaders([
        //     'x-api-key'         => config('services.anthropic.key'),
        //     'anthropic-version' => '2023-06-01',
        //     'content-type'      => 'application/json',
        // ])->post('https://api.anthropic.com/v1/messages', [
        //     'model'      => 'claude-haiku-4-5-20251001',
        //     'max_tokens' => 1024,
        //     'messages'   => [
        //         ['role' => 'user', 'content' => $prompt]
        //     ],
        // ]);
        // 
        // if ($response->successful()) {
        //     $content = trim($response->json('content.0.text'));
        // ============================================

        // ============================================
        // DEVELOPMENT: Groq (free tier)
        // ============================================
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 1024,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt]
            ],
        ]);

        if ($response->successful()) {
            $content = trim($response->json('choices.0.message.content'));
        // ============================================
            
            // Sometimes models return ```json ... ``` despite instructions
            if (str_starts_with($content, '```json')) {
                $content = preg_replace('/^```json|```$/', '', $content);
            }
            
            $data = json_decode(trim($content), true);
            if (is_array($data)) return $data;
        }

        // Fallback
        return [
            [
                "name" => "Drink water first thing",
                "reason" => "A foundational micro-habit everyone benefits from.",
                "category" => "Health",
                "goal" => 30,
                "goal_unit" => "days",
                "priority" => 2
            ]
        ];
    }

    /**
     * Generate a personalized motivational message when a streak is at risk
     */
    public function generateStreakCoachMessage(User $user, Habit $habit): string
    {
        $cacheKey = "streak_coach_{$user->id}_{$habit->id}_" . today()->toDateString();

        return Cache::remember($cacheKey, now()->endOfDay(), function () use ($user, $habit) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'      => 'llama-3.3-70b-versatile',
                'max_tokens' => 150,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => "You write short, personal, motivational messages for a habit tracking app.
                                      Be specific, warm, and direct. Max 2 sentences. No generic advice.
                                      Never start with 'I'. Sound like a supportive friend.",
                    ],
                    [
                        'role'    => 'user',
                        'content' => "Write a streak protection message for {$user->name}.
                                      Habit: '{$habit->name}'
                                      Current streak: {$habit->current_streak} days
                                      Longest ever streak: {$habit->longest_streak} days
                                      They missed yesterday. Today is their chance to restart.
                                      Make it personal and specific to this habit.",
                    ],
                ],
            ]);

            return $response->json('choices.0.message.content')
                ?? "Your {$habit->name} streak needs you today 🔥";
        });
    }

    /**
     * Generate a personalized weekly summary analysis
     */
    public function generateWeeklySummary(User $user): array
    {
        $weekStart = now()->subDays(7)->toDateString();
        $weekEnd   = now()->toDateString();

        // Completions per day
        $completions = $user->completions()
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->where('is_done', true)
            ->with('habit')
            ->get();

        // Active habits count
        $totalHabits = $user->habits()->where('status', 'active')->count();

        // Completion rate per habit
        $habitStats = $user->habits()
            ->where('status', 'active')
            ->withCount(['completions as completed_this_week' => function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('completed_at', [$weekStart, $weekEnd])
                  ->where('is_done', true);
            }])
            ->get()
            ->map(fn($h) => [
                'name'       => $h->name,
                'completed'  => $h->completed_this_week,
                'rate'       => 7 > 0
                    ? round(($h->completed_this_week / 7) * 100)
                    : 0,
                'streak'     => $h->current_streak,
            ]);

        // Best and worst day
        $byDay = $completions->groupBy(fn($c) => \Carbon\Carbon::parse($c->completed_at)->toDateString())
            ->map(fn($c) => $c->count())
            ->sortDesc();

        $bestDay  = $byDay->keys()->first();
        $worstDay = $byDay->keys()->last();

        // Overall completion rate
        $totalPossible   = $totalHabits * 7;
        $totalCompleted  = $completions->count();
        $overallRate     = $totalPossible > 0
            ? round(($totalCompleted / $totalPossible) * 100)
            : 0;

        $dataContext = "
User: {$user->name}
Week: {$weekStart} to {$weekEnd}
Overall completion rate: {$overallRate}%
Total habits completed: {$totalCompleted} of {$totalPossible} possible
Best day: {$bestDay} ({$byDay->first()} habits done)
Worst day: {$worstDay} ({$byDay->last()} habits done)

Per-habit breakdown:
" . $habitStats->map(fn($h) =>
    "- {$h['name']}: {$h['completed']}/7 days ({$h['rate']}%) | streak: {$h['streak']} days"
)->implode("\n");

        $prompt = "
Based on this user's habit data from the past week, generate a weekly summary.
Respond ONLY with a valid JSON object, no preamble, no markdown:
{
  \"what_went_well\": \"2-3 sentences celebrating specific wins with real numbers\",
  \"needs_attention\": \"2-3 sentences about specific habits that struggled, be honest but kind\",
  \"suggestion\": \"One specific, actionable suggestion for next week based on their patterns\",
  \"overall_rate\": {$overallRate},
  \"best_habit\": \"name of the habit with highest completion rate\",
  \"worst_habit\": \"name of the habit with lowest completion rate\",
  \"best_day\": \"{$bestDay}\",
  \"headline\": \"One short punchy headline summarizing the week (max 8 words)\"
}

Data:
{$dataContext}
";

        // DEVELOPMENT: Groq
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 600,
            'messages'   => [
                ['role' => 'system', 'content' => 'You are a habit coach. Respond only with valid JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ]);

        $content = $response->json('choices.0.message.content');

        // Strip any accidental markdown fences
        $clean   = preg_replace('/```json|```/', '', $content);
        $summary = json_decode(trim($clean), true);

        if (!$summary || !isset($summary['what_went_well'])) {
            $summary = [
                'what_went_well'  => "You completed {$totalCompleted} habits this week.",
                'needs_attention' => "Keep pushing — consistency builds over time.",
                'suggestion'      => "Try to complete at least one habit every day next week.",
                'overall_rate'    => $overallRate,
                'best_habit'      => $habitStats->sortByDesc('rate')->first()['name'] ?? '—',
                'worst_habit'     => $habitStats->sortBy('rate')->first()['name'] ?? '—',
                'best_day'        => $bestDay ?? '—',
                'headline'        => "Week {$overallRate}% complete",
            ];
        }

        // Add raw stats for frontend
        $summary['total_completed'] = $totalCompleted;
        $summary['total_possible']  = $totalPossible;
        $summary['habit_stats']     = $habitStats->toArray();
        $summary['generated_at']    = now()->toISOString();

        $user->update([
            'last_weekly_summary'      => $summary,
            'last_weekly_summary_date' => today(),
        ]);

        return $summary;
    }
}


