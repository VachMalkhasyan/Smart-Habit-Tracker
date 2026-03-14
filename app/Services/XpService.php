<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpLog;
use App\Notifications\LeveledUp;

class XpService
{
    // XP amounts per action
    const XP_COMPLETE_HABIT     = 10;
    const XP_FIRST_HABIT_DAY    = 5;   // bonus for first habit of the day
    const XP_ALL_HABITS_DONE    = 25;  // bonus for completing ALL habits today
    const XP_STREAK_BONUS       = 3;   // per streak day (e.g. 7-day streak = 21 bonus)
    const XP_POMODORO_SESSION   = 15;
    const XP_LEVEL_MILESTONE    = 50;  // bonus on level up

    // XP required per level (exponential)
    public static function xpForLevel(int $level): int
    {
        return (int) (100 * pow($level, 1.5));
    }

    public static function levelFromXp(int $xp): int
    {
        $level = 1;
        while (self::xpForLevel($level + 1) <= $xp) {
            $level++;
        }
        return $level;
    }

    public static function award(User $user, int $amount, string $reason, string $sourceType = null, int $sourceId = null): array
    {
        $oldLevel = $user->level;

        // Record XP
        XpLog::create([
            'user_id'     => $user->id,
            'amount'      => $amount,
            'reason'      => $reason,
            'source_type' => $sourceType,
            'source_id'   => $sourceId,
        ]);

        $newXp    = $user->xp + $amount;
        $newLevel = self::levelFromXp($newXp);

        $user->update([
            'xp'    => $newXp,
            'level' => $newLevel,
        ]);

        $leveledUp = $newLevel > $oldLevel;

        // Award level up bonus
        if ($leveledUp) {
            XpLog::create([
                'user_id' => $user->id,
                'amount'  => self::XP_LEVEL_MILESTONE,
                'reason'  => "Reached level {$newLevel}! 🎉",
            ]);
            $user->increment('xp', self::XP_LEVEL_MILESTONE);

            // Send LeveledUp notification (database + broadcast)
            $levelTitle = self::getLevelTitle($newLevel);
            $user->notify(new LeveledUp($newLevel, $levelTitle, $user->fresh()->xp));
        }

        return [
            'xp_awarded' => $amount,
            'total_xp'   => $newXp,
            'new_level'  => $newLevel,
            'leveled_up' => $leveledUp,
        ];
    }

    public static function revoke(User $user, int $amount, string $reason, string $sourceType = null, int $sourceId = null): array
    {
        $oldLevel = $user->level;

        // Record XP deductive log
        XpLog::create([
            'user_id'     => $user->id,
            'amount'      => -$amount,
            'reason'      => $reason,
            'source_type' => $sourceType,
            'source_id'   => $sourceId,
        ]);

        $newXp    = max(0, $user->xp - $amount);
        $newLevel = self::levelFromXp($newXp);

        $user->update([
            'xp'    => $newXp,
            'level' => $newLevel,
        ]);

        $leveledDown = $newLevel < $oldLevel;

        return [
            'xp_revoked'  => $amount,
            'total_xp'    => $newXp,
            'new_level'   => $newLevel,
            'leveled_down'=> $leveledDown,
        ];
    }

    public static function progressToNextLevel(User $user): array
    {
        $currentLevelXp = self::xpForLevel($user->level);
        $nextLevelXp    = self::xpForLevel($user->level + 1);
        $progressXp     = $user->xp - $currentLevelXp;
        $neededXp       = $nextLevelXp - $currentLevelXp;

        return [
            'current_xp'   => $user->xp,
            'level'        => $user->level,
            'progress_xp'  => $progressXp,
            'needed_xp'    => $neededXp,
            'percent'      => min(100, round(($progressXp / $neededXp) * 100)),
            'next_level'   => $user->level + 1,
        ];
    }

    public static function getLevelTitle(int $level): string
    {
        return match(true) {
            $level >= 50 => '🏆 Legend',
            $level >= 40 => '💎 Diamond',
            $level >= 30 => '🥇 Gold',
            $level >= 20 => '🥈 Silver',
            $level >= 10 => '🥉 Bronze',
            $level >= 5  => '⭐ Rising Star',
            default      => '🌱 Beginner',
        };
    }
}
