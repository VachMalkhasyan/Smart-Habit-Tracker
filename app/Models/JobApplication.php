<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'company_name', 'role_title', 'job_url',
        'status', 'priority', 'salary_min', 'salary_max',
        'currency', 'location', 'is_remote', 'applied_date',
        'notes', 'order', 'ats_score', 'ats_analysis', 'ats_analyzed_at',
    ];

    protected $casts = [
        'is_remote'       => 'boolean',
        'applied_date'    => 'date',
        'salary_min'      => 'integer',
        'salary_max'      => 'integer',
        'priority'        => 'integer',
        'ats_analysis'    => 'array',
        'ats_analyzed_at' => 'datetime',
    ];

    // Status labels for frontend
    public static array $statuses = [
        'wishlist'     => ['label' => 'Wishlist',     'color' => 'gray'],
        'applied'      => ['label' => 'Applied',      'color' => 'blue'],
        'phone_screen' => ['label' => 'Phone Screen', 'color' => 'yellow'],
        'interview'    => ['label' => 'Interview',    'color' => 'purple'],
        'offer'        => ['label' => 'Offer',        'color' => 'green'],
        'rejected'     => ['label' => 'Rejected',     'color' => 'red'],
        'withdrawn'    => ['label' => 'Withdrawn',    'color' => 'orange'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interviews(): \Illuminate\Database\Eloquent\Relations\Relation
    {
        return $this->hasMany(JobInterview::class)->orderBy('scheduled_at');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(JobContact::class);
    }

    public function nextInterview(): ?JobInterview
    {
        return $this->interviews()
            ->where('scheduled_at', '>=', now())
            ->where('outcome', 'pending')
            ->orderBy('scheduled_at')
            ->first();
    }

    // Salary display helper
    public function getSalaryRangeAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) return null;
        if ($this->salary_min && $this->salary_max) {
            return "{$this->currency} " .
                number_format($this->salary_min) . ' – ' .
                number_format($this->salary_max);
        }
        return "{$this->currency} " .
            number_format($this->salary_min ?? $this->salary_max);
    }
}
