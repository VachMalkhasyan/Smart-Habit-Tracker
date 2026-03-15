<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoodLog extends Model
{
    protected $fillable = [
        'user_id', 'score', 'emoji', 'label', 'note', 'tags', 'logged_date'
    ];

    protected $casts = [
        'tags'        => 'array',
        'logged_date' => 'date',
        'score'       => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('logged_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Static helpers for score to emoji mapping
     */
    public static function emojiForScore(int $score): string
    {
        return match($score) {
            1 => '😢',
            2 => '😕',
            3 => '😐',
            4 => '🙂',
            5 => '😄',
            default => '😐'
        };
    }

    /**
     * Static helpers for score to label mapping
     */
    public static function labelForScore(int $score): string
    {
        return match($score) {
            1 => 'Terrible',
            2 => 'Bad',
            3 => 'Okay',
            4 => 'Good',
            5 => 'Amazing',
            default => 'Okay'
        };
    }
}
