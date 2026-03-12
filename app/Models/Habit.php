<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Habit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'name', 'description',
        'goal', 'goal_unit', 'status', 'start_date',
        'deadline_value', 'deadline_unit', 'repeat_count',
        'priority', 'current_streak', 'longest_streak'
    ];

    protected $casts = [
        'start_date' => 'date',
        'is_done'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HabitCategory::class, 'category_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }

    public function todayCompletion(): HasMany
    {
        return $this->hasMany(Completion::class)
            ->whereDate('completed_at', today());
    }
}
