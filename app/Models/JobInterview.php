<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobInterview extends Model
{
    protected $fillable = [
        'job_application_id', 'user_id', 'interview_type',
        'scheduled_at', 'interviewer_name', 'notes',
        'ai_prep', 'ai_feedback', 'outcome',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->scheduled_at->isFuture() && $this->outcome === 'pending';
    }
}
