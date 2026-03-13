<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Completion extends Model
{
    protected $fillable = ['habit_id', 'user_id', 'completed_at', 'count', 'is_done'];

    protected $casts = [
        'completed_at' => 'date',
        'is_done'      => 'boolean',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function cheers(): HasMany
    {
        return $this->hasMany(Cheer::class);
    }
}
