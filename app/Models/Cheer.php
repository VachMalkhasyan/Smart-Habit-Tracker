<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheer extends Model
{
    protected $fillable = ['user_id', 'completion_id', 'emoji'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completion(): BelongsTo
    {
        return $this->belongsTo(Completion::class);
    }
}
