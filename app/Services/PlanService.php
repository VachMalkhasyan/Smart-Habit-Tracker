<?php

namespace App\Services;

use App\Models\User;

class PlanService
{
    public static function limits(User $user): array
    {
        return config('plans.' . $user->plan, config('plans.free'));
    }

    public static function can(User $user, string $feature): bool
    {
        return (bool) (static::limits($user)[$feature] ?? false);
    }

    public static function limit(User $user, string $feature): int
    {
        return (int) (static::limits($user)[$feature] ?? 0);
    }

    public static function hasReached(User $user, string $feature, int $current): bool
    {
        $limit = static::limit($user, $feature);
        if ($limit === -1) return false;
        return $current >= $limit;
    }

    public static function aiModel(User $user): string
    {
        return static::limits($user)['ai_model'] ?? 'groq';
    }

    public static function upgradeMessage(string $feature): string
    {
        return match($feature) {
            'habits_limit'        => 'Upgrade to Pro for unlimited habits.',
            'ai_messages_per_day' => 'Upgrade to Pro for unlimited AI messages.',
            'job_tracker'         => 'Upgrade to Pro to unlock Job Tracker.',
            'job_apps_limit'      => 'Upgrade to Pro for up to 20 applications, or Max for unlimited.',
            'friends'             => 'Upgrade to Pro to unlock Friends & Accountability.',
            'mood_correlation'    => 'Upgrade to Pro to see mood-habit correlation.',
            'cv_upload'           => 'Upgrade to Max to upload your CV and get ATS scoring.',
            'ats_scoring'         => 'Upgrade to Max for AI-powered ATS scoring.',
            'cover_letter'        => 'Upgrade to Pro for AI cover letter generation.',
            'company_research'    => 'Upgrade to Pro for AI company research.',
            'export'              => 'Upgrade to Pro to export your data.',
            'weekly_ai_summary'   => 'Upgrade to Pro for weekly AI summaries.',
            'diary'               => 'Upgrade to Pro to unlock the Diary feature.',
            default               => 'Upgrade your plan to unlock this feature.',
        };
    }

    public static function requiredPlan(string $feature): string
    {
        $maxFeatures = ['cv_upload', 'ats_scoring'];
        return in_array($feature, $maxFeatures) ? 'max' : 'pro';
    }
}
