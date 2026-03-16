<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCv extends Model
{
    protected $fillable = [
        'user_id', 'title', 'file_path', 'file_name',
        'file_type', 'raw_text', 'parsed_data', 'is_active'
    ];

    protected $casts = [
        'parsed_data' => 'array',
        'is_active'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Summary for AI context (keep tokens low)
     */
    public function getSummaryForAi(): string
    {
        $parsed = $this->parsed_data;
        if (!$parsed) return $this->raw_text;

        $roles = $parsed['roles'] ?? [];
        $skills = $parsed['skills'] ?? [];

        return "
            Name: " . ($parsed['name'] ?? 'Unknown') . "
            Skills: " . implode(', ', array_slice($skills, 0, 15)) . "
            Experience: " . ($parsed['years_experience'] ?? '?') . " years
            Recent roles: " . implode(', ', array_slice($roles, 0, 3)) . "
            Education: " . ($parsed['education'] ?? 'Not specified') . "
            Summary: " . ($parsed['summary'] ?? '') . "
        ";
    }
}
