<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HabitCategory extends Model
{
    protected $fillable = ['user_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class, 'category_id');
    }

    public function scopeAvailableFor($query, $userId)
    {
        return $query->whereNull('user_id')->orWhere('user_id', $userId);
    }
}
