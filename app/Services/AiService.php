<?php

// ============================================
// AI SERVICE
// Provider: Groq (Llama 3.3 / 3.1)
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
     * Builds the full personalized system prompt.
     * ALWAYS uses fresh DB queries — never stale cached model data.
     */
    public function buildSystemPrompt(User $user): string
    {
        // Always fresh — never use cached $user relationships
        $user  = $user->fresh();
        $today = now()->toDateString();

        // ── HABITS ──
        $activeHabits = \App\Models\Habit::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['completions' => fn($q) => $q->whereDate('completed_at', $today)])
            ->orderBy('priority')
            ->get();

        $totalHabits    = $activeHabits->count();
        $completedToday = $activeHabits->filter(
            fn($h) => $h->completions->where('is_done', true)->count() > 0
        )->count();
        $pendingToday   = $totalHabits - $completedToday;
        $allDoneToday   = $totalHabits > 0 && $pendingToday === 0;

        $habitsContext = $activeHabits->map(fn($h) =>
            "- {$h->name} | Streak: {$h->current_streak}d | " .
            "Priority: " . (['', 'High', 'Medium', 'Low'][$h->priority] ?? $h->priority) . " | " .
            "Today: " . ($h->completions->where('is_done', true)->count() > 0 ? '✅ Done' : '⏳ Pending')
        )->implode("\n");

        // ── MOOD ──
        $todayMood = \App\Models\MoodLog::where('user_id', $user->id)
            ->whereDate('logged_date', $today)
            ->first();

        $weeklyMoods = \App\Models\MoodLog::where('user_id', $user->id)
            ->whereBetween('logged_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ])
            ->orderBy('logged_date')
            ->get()
            ->map(fn($m) =>
                now()->parse($m->logged_date)->format('D') .
                ": {$m->emoji} {$m->label} ({$m->score}/5)" .
                ($m->note ? " — \"{$m->note}\"" : '')
            )->implode(', ');

        $moodContext = $todayMood
            ? "Today: {$todayMood->emoji} {$todayMood->label} ({$todayMood->score}/5)"
              . ($todayMood->note ? " — \"{$todayMood->note}\"" : '')
            : 'Not logged today';

        // ── XP & LEVEL ──
        $xpProgress = \App\Services\XpService::progressToNextLevel($user);
        $levelTitle  = \App\Services\XpService::getLevelTitle($user->level);

        // ── STREAKS ──
        $topStreaks = $activeHabits
            ->sortByDesc('current_streak')
            ->take(3)
            ->map(fn($h) => (string)$h->name . ': ' . (string)$h->current_streak . ' days')
            ->implode(', ');

        $streaksAtRisk = $activeHabits
            ->filter(fn($h) =>
                $h->current_streak > 0 &&
                $h->completions->where('is_done', true)->count() === 0
            )
            ->map(fn($h) => (string)$h->name . ' (' . (string)$h->current_streak . ' days at risk)')
            ->implode(', ');

        // ── ANALYTICS — weak days ──
        $weakDays = '';
        $weakEntries = $user->completions()
            ->where('is_done', '=', false)
            ->where('completed_at', '>=', now()->subDays(14))
            ->get()
            ->groupBy(fn($c) => \Carbon\Carbon::parse($c->completed_at)->format('l'))
            ->map->count()
            ->sortDesc();
        
        if ($weakEntries->isNotEmpty()) {
            $weakDays = "Historically, you struggle most on " . $weakEntries->keys()->take(2)->implode(' and ') . ".";
        }

        // ── JOB TRACKER (If active) ──
        $jobContext = "";
        if ($user->plan !== 'free') {
            $stats = $user->jobSearchStats();
            $jobContext = "\n── JOB SEARCH ──\n" .
                "Status: {$stats['applied']} applied, {$stats['interviewing']} interviewing, {$stats['offers']} offers.\n";
            
            $nextJob = $user->jobApplications()
                ->where('status', '=', 'wishlist')
                ->where('priority', '=', 1)
                ->first();
            if ($nextJob) {
                $jobContext .= "High Priority Target: {$nextJob->role_title} at {$nextJob->company_name}.\n";
            }
        }

        return "
            You are GrowthZone AI, a high-performance habit coach and life strategist.
            Tone: Encouraging, data-driven, concise, and slightly competitive.
            Current User: {$user->name}
            Level: {$user->level} ({$levelTitle}) | XP: {$user->xp} ({$xpProgress['percent']}% to next level)

            ── TODAY'S STATS ({$today}) ──
            Active Habits: {$totalHabits}
            Completed: {$completedToday} | Pending: {$pendingToday}
            " . ($allDoneToday ? "🎉 ALL HABITS COMPLETED TODAY!" : "") . "

            ── HABITS STATUS ──
            {$habitsContext}

            ── MOOD LOGS ──
            {$moodContext}
            Weekly Trend: {$weeklyMoods}

            ── STREAK INSIGHTS ──
            Winning at: {$topStreaks}
            At Risk: " . ($streaksAtRisk ?: 'None') . "
            {$weakDays}
            {$jobContext}

            ── GUIDELINES ──
            1. Keep responses under 150 words.
            2. Reference specific habits or mood entries.
            3. Use the user's level/XP to motivate them (e.g. 'Only 20% more for Level 5!').
            4. If they finished everything today, celebrate it! 
            5. Provide actionable, micro-advice.
            6. You are talking to them in a chat interface. Be conversational but focused.
        ";
    }

    /**
     * Main method. Saves user message, checks summary, sends to Groq, saves response.
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

        // 4. Call API (Always use Groq)
        $groqMessages = $messages; 

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
        $tokensUsed = (int)$response->json('usage.prompt_tokens', 0) + (int)$response->json('usage.completion_tokens', 0);

        // 5. Save assistant response
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'tokens_used' => $tokensUsed,
        ]);

        // 6. Increment counts and reward XP
        $conversation->increment('tokens_used', $tokensUsed);
        
        $user->increment('xp', 2);

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
            $summary = $response->json('choices.0.message.content');
            $tokensUsed = (int)$response->json('usage.prompt_tokens', 0) + (int)$response->json('usage.completion_tokens', 0);
            
            $conversation->update([
                'summary' => $summary,
                'tokens_used' => $conversation->tokens_used + $tokensUsed
            ]);
            
            // Delete old messages that were summarized
            $messagesToSummarize->each->delete();
        }
    }

    /**
     * Generate a personalized affirmation (Home dashboard)
     */
    public function generateDailyAffirmation(User $user, bool $forceRegenerate = false): string
    {
        $today = today();
        
        // Cache for the day unless forced
        if (!$forceRegenerate && $user->affirmation_date?->setHour(0)->setMinute(0)->setSecond(0)->equalTo($today)) {
            return $user->daily_affirmation ?? "You're doing great, {$user->name}!";
        }

        // Fresh data context
        $moodContext = \App\Models\MoodLog::where('user_id', $user->id)
            ->whereDate('logged_date', $today)
            ->first()
            ? "Mood: " . \App\Models\MoodLog::where('user_id', $user->id)
                ->whereDate('logged_date', $today)
                ->first()->label
            : "Mood: not logged yet";

        $streakContext = $user->habits()
            ->where('status', 'active')
            ->orderByDesc('current_streak')
            ->first();

        $completedToday = $user->completions()
            ->whereDate('completed_at', today())
            ->where('is_done', true)
            ->count();

        $totalHabits = $user->habits()->where('status', 'active')->count();

        // Always use Groq
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
        $cvSummary      = $cv->getSummaryForAi();
        $jobDescription = $this->fetchJobDescription($job->job_url);

        if (empty($jobDescription) && $job->notes) {
            $jobDescription = $job->notes;
        }

        $jobDescriptionSource = 'title_only';
        if (!empty($jobDescription)) {
            $jobDescriptionSource = ($job->job_url && str_contains($jobDescription, ' '))
                ? 'url_fetched'
                : 'notes';
        } else {
            $jobDescription = "Role: {$job->role_title} at {$job->company_name}. "
                . ($job->location ? "Location: {$job->location}." : '')
                . ($job->is_remote ? ' Remote position.' : '');
        }

        $prompt = "
            You are an ATS (Applicant Tracking System) and career coach.
            Analyze how well this candidate's CV matches the job.

            JOB:
            Company: {$job->company_name}
            Role: {$job->role_title}
            JOB DESCRIPTION:
            {$jobDescription}

            CANDIDATE CV:
            {$cvSummary}

            Respond ONLY with valid JSON. No markdown. No preamble.
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
        $clean    = preg_replace('/```json\s*|\s*```/', '', $content);
        preg_match('/\{.*\}/s', $clean, $matches);
        $jsonStr  = $matches[0] ?? '{}';
        $analysis = json_decode($jsonStr, true);

        if (!$analysis || !isset($analysis['score'])) {
            $analysis = [
                'score' => 0,
                'verdict' => 'Analysis failed',
                'summary' => 'Could not analyze.',
                'matching_skills' => [],
                'missing_skills' => [],
                'strengths' => [],
                'gaps' => [],
                'recommendation' => 'Try again',
                'cv_tips_for_this_role' => [],
                'job_description_source' => 'error',
            ];
        }

        if (empty($analysis['job_description_source'])) {
            $analysis['job_description_source'] = $jobDescriptionSource;
        }

        $job->update([
            'ats_score'       => $analysis['score'] ?? 0,
            'ats_analysis'    => $analysis,
            'ats_analyzed_at' => now(),
        ]);

        return $analysis;
    }

    public function improveCv(UserCv $cv): array
    {
        $prompt = "Review this CV and give specific, actionable improvement suggestions. Respond ONLY with valid JSON.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.3-70b-versatile',
            'max_tokens' => 1000,
            'messages'   => [
                ['role'    => 'system', 'content' => 'You are a professional CV coach. Respond ONLY with valid JSON.'],
                ['role' => 'user', 'content' => $prompt . "\nCV TEXT:\n" . $cv->raw_text],
            ],
        ]);

        $content = $response->json('choices.0.message.content') ?? '{}';
        $clean   = preg_replace('/```json|```/', '', $content);

        return json_decode(trim($clean), true) ?? [];
    }

    public function fetchJobDescription(?string $url): string
    {
        if (!$url) return '';

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();
                
                if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
                    foreach ($matches[1] as $match) {
                        $json = json_decode(trim($match), true);
                        if (isset($json['@type'])) {
                            $types = is_array($json['@type']) ? $json['@type'] : [$json['@type']];
                            if (in_array('JobPosting', $types) && isset($json['description'])) {
                                return strip_tags(str_replace(['<br>', '<p>', '<li>'], ["\n", "\n\n", "\n• "], $json['description']));
                            }
                        }
                    }
                }

                $text = preg_replace('/<script\b[^>]*>([\s\S]*?)<\/script>/i', '', $html);
                $text = preg_replace('/<style\b[^>]*>([\s\S]*?)<\/style>/i', '', $text);
                $text = strip_tags($text);
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);

                return $this->cleanScrapedText(substr($text, 0, 8000));
            }
        } catch (\Exception $e) {
            Log::info("Could not fetch job description from URL: {$url}");
        }

        return '';
    }

    public function cleanScrapedText(string $rawText): string
    {
        if (strlen($rawText) < 100) return $rawText;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'      => 'llama-3.1-8b-instant',
            'max_tokens' => 1000,
            'messages'   => [
                ['role' => 'system', 'content' => 'You extract job descriptions from messy scraped text.'],
                ['role' => 'user', 'content' => "Extract ONLY the relevant job details from this text:\n\n" . $rawText],
            ],
        ]);

        return $response->json('choices.0.message.content') ?? substr($rawText, 0, 3000);
    }
}
