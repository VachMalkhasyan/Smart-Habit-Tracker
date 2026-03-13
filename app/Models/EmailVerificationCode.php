<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationCode extends Model
{
    protected $fillable = ['user_id', 'code', 'expires_at', 'resent_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'resent_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function canResend(): bool
    {
        if (!$this->resent_at) return true;
        return now()->diffInSeconds($this->resent_at) >= 60;
    }

    public function secondsUntilResend(): int
    {
        if (!$this->resent_at) return 0;
        $diff = 60 - now()->diffInSeconds($this->resent_at);
        return max(0, $diff);
    }
}
