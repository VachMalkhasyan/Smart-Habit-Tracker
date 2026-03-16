<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobContact extends Model
{
    protected $fillable = [
        'user_id', 'job_application_id', 'name', 'role',
        'company', 'linkedin_url', 'email',
        'last_contact_date', 'notes',
    ];

    protected $casts = [
        'last_contact_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }
}
