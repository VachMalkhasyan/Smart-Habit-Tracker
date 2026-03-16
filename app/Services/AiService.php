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
use App\Models\JobApplication;
use App\Models\JobInterview;
use App\Models\UserCv;
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

            
        $moodThisWeek = $user->moodLogs()->thisWeek()->orderBy('logged_date')->get();
        $moodContext  = $moodThisWeek->isEmpty()
            ? 'Not tracking mood yet'
            : $moodThisWeek->map(fn($m) =>
                "{$m->logged_date->format('D')}: {$m->emoji} {$m->label} ({$m->score}/5)"
                . ($m->note ? " — \"{$m->note}\"" : '')
              )->implode(', ');

        $recentDiary = "No diary entries yet"; // Placeholder
        $goals = $user->settings['goals'] ?? 'None explicitly set';
        $jobContext = $this->generateJobSearchSummary($user);

        $cvContext = '';
        $activeCV  = $user->activeCV();
        if ($activeCV) {
            $parsed   = $activeCV->parsed_data ?? [];
            $cvContext = "
USER'S CV / BACKGROUND:
- Skills: " . implode(', ', array_slice($parsed['skills'] ?? [], 0, 10)) . "
- Experience: " . ($parsed['years_experience'] ?? '?') . " years
- Recent roles: " . implode(', ', array_slice($parsed['roles'] ?? [], 0, 3)) . "
- Education: " . ($parsed['education'] ?? 'Not specified');
        }

        return "You are a personal habit coach and life assistant for {$user->name}.
" . ($cvContext ? $cvContext . "\n" : "") . "
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

JOB SEARCH STATUS:
{$jobContext}

MOOD THIS WEEK:
{$moodContext}
Weekly average: {$user->weeklyMoodAverage()}/5

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
     * Generate daily affirmation based on mood and habit performance
     */
    public function generateDailyAffirmation(User $user, bool $forceRegenerate = false): string
    {
        // Skip if already generated today (unless forced)
        if (!$forceRegenerate
            && $user->affirmation_date?->isToday()
            && $user->daily_affirmation) {
            return $user->daily_affirmation;
        }

        $mood         = $user->todaysMood();
        $moodContext  = $mood
            ? "Today's mood: {$mood->emoji} {$mood->label} ({$mood->score}/5)"
              . ($mood->note ? ", note: \"{$mood->note}\"" : '')
            : 'No mood logged today yet';

        $streakContext = $user->habits()
            ->where('status', 'active')
            ->where('current_streak', '>', 0)
            ->orderByDesc('current_streak')
            ->first();

        $completedToday = $user->completions()
            ->whereDate('completed_at', today())
            ->where('is_done', true)
            ->count();

        $totalHabits = $user->habits()->where('status', 'active')->count();

        // DEVELOPMENT: Groq
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 120,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => "You write one short personal affirmation (max 2 sentences) for a habit
                                  tracking app user. Be warm, specific to their data, never generic.
                                  Reference their actual mood or habits. Sound like a caring friend.
                                  Never start with 'I'. No hashtags.",
                ],
                [
                    'role'    => 'user',
                    'content' => "Write a personalized affirmation for {$user->name}.
                                  {$moodContext}
                                  Habits completed today: {$completedToday} of {$totalHabits}
                                  Best current streak: " . ($streakContext
                                      ? "{$streakContext->name} at {$streakContext->current_streak} days"
                                      : 'none yet') . "
                                  Make it feel personal to their exact situation right now.",
                ],
            ],
        ]);

        $affirmation = $response->json('choices.0.message.content')
            ?? "Every step forward counts — keep going {$user->name} 💙";

        $user->update([
            'daily_affirmation' => $affirmation,
            'affirmation_date'  => today(),
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

    public function generateInterviewPrep(User $user, JobInterview $interview): string
    {
        $app = $interview->application;

        // Get past interview history for this user
        $pastInterviews = $user->jobInterviews()
            ->where('outcome', '!=', 'pending')
            ->where('id', '!=', $interview->id)
            ->with('application')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($i) => "- {$i->interview_type} at {$i->application->company_name}: " .
                "outcome={$i->outcome}" .
                ($i->notes ? ", notes: \"{$i->notes}\"" : ''))
            ->implode("\n");

        $prompt = "
            Generate a personalized interview preparation guide for {$user->name}.

            UPCOMING INTERVIEW:
            Company: {$app->company_name}
            Role: {$app->role_title}
            Type: {$interview->interview_type}
            Date: {$interview->scheduled_at->format('D, M j Y g:i A')}
            Interviewer: " . ($interview->interviewer_name ?? 'Unknown') . "

            PAST INTERVIEW HISTORY:
            " . ($pastInterviews ?: 'No past interviews yet') . "

            Generate a prep guide with these sections:
            1. Key things to research about {$app->company_name}
            2. Likely questions for a {$interview->interview_type} interview for this role
            3. Suggested answers framework based on the role
            4. Questions to ask the interviewer
            5. Common mistakes to avoid (based on past interview history if available)

            Be specific to this exact role and company. Max 400 words.
        ";

        // DEVELOPMENT: Groq
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 800,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => 'You are an expert career coach. Give specific, actionable advice. Never be generic.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $prep = $response->json('choices.0.message.content')
            ?? 'Research the company thoroughly and prepare examples using the STAR method.';

        // Save to interview record
        $interview->update(['ai_prep' => $prep]);

        return $prep;
    }

    public function generateCoverLetter(
        User $user,
        JobApplication $application,
        string $jobDescription
    ): string {
        $prompt = "
            Write a professional, personalized cover letter for {$user->name}.

            JOB DETAILS:
            Company: {$application->company_name}
            Role: {$application->role_title}
            Location: " . ($application->location ?? 'Not specified') . "
            Remote: " . ($application->is_remote ? 'Yes' : 'No') . "

            JOB DESCRIPTION:
            {$jobDescription}

            USER CONTEXT:
            - Has been actively job hunting
            - Applied to {$this->getApplicationCount($user)} positions so far
            - Current job search focus: {$application->role_title} roles
";

        $activeCV = $user->activeCV();
        if ($activeCV) {
            $prompt .= "
            CANDIDATE CV SUMMARY:
            " . $activeCV->getSummaryForAi() . "
            Use specific skills and experience from this CV to make the cover letter authentic and targeted.
";
        }

        $prompt .= "
            Write a compelling 3-paragraph cover letter:
            1. Strong opening that shows genuine interest in THIS company
            2. Why they're the right fit for THIS specific role
            3. Call to action closing

            Tone: Professional but human. Not robotic. Max 250 words.
            Do NOT use generic phrases like 'I am writing to express my interest'.
        ";

        // DEVELOPMENT: Groq
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 600,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => 'You write compelling, human cover letters. Never use clichés or corporate speak.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response->json('choices.0.message.content')
            ?? 'Unable to generate cover letter. Please try again.';
    }

    private function getApplicationCount(User $user): int
    {
        return $user->jobApplications()->whereNotNull('applied_date')->count();
    }

    public function generateCompanyResearch(
        User $user,
        string $companyName,
        string $roleTitle
    ): string {
        $cacheKey = 'company_research_' . \Illuminate\Support\Str::slug($companyName) . '_' . \Illuminate\Support\Str::slug($roleTitle);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($companyName, $roleTitle) {
            $prompt = "
                Generate a concise company research brief for a job candidate.

                Company: {$companyName}
                Role applying for: {$roleTitle}

                Provide:
                1. What the company does (2-3 sentences)
                2. Company culture & values (based on what's generally known)
                3. Typical interview process for this type of role
                4. Estimated salary range for {$roleTitle}
                5. 3 smart questions to ask during the interview
                6. 2-3 things to research further before the interview

                Be specific and practical. Max 300 words.
                Note: Base this on general knowledge — be honest if you're uncertain about specifics.
            ";

            // DEVELOPMENT: Groq
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type'  => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'      => 'llama-3.3-70b-versatile',
                'max_tokens' => 600,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a career research assistant. Be specific and practical.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            return $response->json('choices.0.message.content')
                ?? 'Research this company on LinkedIn, Glassdoor, and their official website.';
        });
    }

    public function generateJobSearchSummary(User $user): string
    {
        $stats = $user->jobSearchStats();

        if ($stats['total'] === 0) {
            return 'Not actively job hunting yet.';
        }

        $upcomingInterviews = $user->jobInterviews()
            ->where('scheduled_at', '>=', now())
            ->where('outcome', 'pending')
            ->with('application')
            ->orderBy('scheduled_at')
            ->take(3)
            ->get()
            ->map(fn($i) =>
                "- {$i->interview_type} at {$i->application->company_name} " .
                "on {$i->scheduled_at->format('D M j g:i A')}")
            ->implode("\n");

        $recentRejections = $user->jobApplications()
            ->where('status', 'rejected')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        return "
            Job search active: {$stats['total']} total applications
            - Applied: {$stats['applied']}
            - In interview process: {$stats['interviewing']}
            - Offers received: {$stats['offers']}
            - Rejected: {$stats['rejected']}
            - Applied this week: {$stats['this_week']}
            - Recent rejections (last 7 days): {$recentRejections}
            " . ($upcomingInterviews ? "\nUpcoming interviews:\n{$upcomingInterviews}" : '');
    }

    public function parseCv(string $rawText): array
    {
        $prompt = "
            Extract structured data from this CV/resume text.
            Respond ONLY with valid JSON, no preamble, no markdown fences.

            {
              \"name\": \"full name\",
              \"email\": \"email if present\",
              \"phone\": \"phone if present\",
              \"location\": \"city/country if present\",
              \"summary\": \"professional summary in 2 sentences\",
              \"years_experience\": 5,
              \"skills\": [\"Laravel\", \"Vue.js\", \"MySQL\"],
              \"roles\": [\"Senior Backend Developer at X\", \"Junior Dev at Y\"],
              \"education\": \"BSc Computer Science, University Name\",
              \"languages\": [\"English\", \"Armenian\"],
              \"strengths\": [\"3 key professional strengths\"],
              \"improvement_areas\": [\"2-3 areas that could be stronger\"]
            }

            CV TEXT:
            {$rawText}
        ";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 1000,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => 'You extract structured data from CVs. Respond ONLY with valid JSON. No markdown.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content = $response->json('choices.0.message.content') ?? '{}';
        $clean   = preg_replace('/```json|```/', '', $content);

        return json_decode(trim($clean), true) ?? [];
    }

    public function scoreJobFit(UserCv $cv, JobApplication $job): array
    {
        $cvSummary = $cv->getSummaryForAi();

        $prompt = "
            You are an ATS (Applicant Tracking System) and career coach.
            Analyze how well this candidate's CV matches the job.

            JOB:
            Company: {$job->company_name}
            Role: {$job->role_title}
            Location: " . ($job->location ?? 'Not specified') . "
            " . ($job->notes ? "Job notes: {$job->notes}" : '') . "

            CANDIDATE CV SUMMARY:
            {$cvSummary}

            Respond ONLY with valid JSON:
            {
              \"score\": 78,
              \"verdict\": \"Strong Match\",
              \"summary\": \"2-3 sentence honest assessment\",
              \"matching_skills\": [\"skill1\", \"skill2\"],
              \"missing_skills\": [\"skill3\", \"skill4\"],
              \"strengths\": [\"3 specific strengths for this role\"],
              \"gaps\": [\"2-3 honest gaps or concerns\"],
              \"recommendation\": \"Apply confidently / Apply with adjustments / Consider carefully\",
              \"cv_tips_for_this_role\": [\"specific tip 1\", \"specific tip 2\", \"specific tip 3\"]
            }

            Score guide: 90-100=Perfect, 75-89=Strong, 60-74=Good, 40-59=Fair, below 40=Weak
            Be honest. Do not inflate scores.
        ";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 800,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => 'You are an honest ATS scoring system. Respond ONLY with valid JSON.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content  = $response->json('choices.0.message.content') ?? '{}';
        // Strip any markdown fences Groq might add
        $clean    = preg_replace('/```json\s*|\s*```/', '', $content);
        $clean    = trim($clean);
        // Find JSON object — sometimes Groq adds text before/after
        preg_match('/\{.*\}/s', $clean, $matches);
        $jsonStr  = $matches[0] ?? '{}';
        $analysis = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($analysis['score'])) {
            Log::error('ATS JSON parse failed: ' . $content);
            $analysis = [
                'score'           => 50,
                'verdict'         => 'Analysis incomplete',
                'summary'         => 'Could not fully analyze. Please try again.',
                'matching_skills' => [],
                'missing_skills'  => [],
                'strengths'       => [],
                'gaps'            => [],
                'recommendation'  => 'Try again',
                'cv_tips_for_this_role' => [],
            ];
        }

        // Save to job application
        $job->update([
            'ats_score'       => $analysis['score'],
            'ats_analysis'    => $analysis,
            'ats_analyzed_at' => now(),
        ]);

        return $analysis;
    }

    public function improveCv(UserCv $cv): array
    {
        $prompt = "
            Review this CV and give specific, actionable improvement suggestions.
            Respond ONLY with valid JSON:
            {
              \"overall_score\": 72,
              \"summary_feedback\": \"2-3 sentence overall assessment\",
              \"sections\": [
                {
                  \"section\": \"Professional Summary\",
                  \"score\": 65,
                  \"issue\": \"What's wrong or missing\",
                  \"suggestion\": \"Specific fix with example\"
                }
              ],
              \"quick_wins\": [\"3 things to fix today that have biggest impact\"],
              \"missing_sections\": [\"sections that should be added\"],
              \"ats_keywords_to_add\": [\"keywords missing that ATS systems look for\"]
            }

            CV TEXT:
            {$cv->raw_text}
        ";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 1000,
            'messages'   => [
                [
                    'role'    => 'system',
                    'content' => 'You are a professional CV coach. Respond ONLY with valid JSON.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $content = $response->json('choices.0.message.content') ?? '{}';
        $clean   = preg_replace('/```json|```/', '', $content);

        return json_decode(trim($clean), true) ?? [];
    }
}


