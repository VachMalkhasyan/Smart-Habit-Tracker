<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = ['user_id', 'title', 'summary', 'tokens_used'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at', 'ASC');
    }

    /**
     * Get the 6 most recent messages, ordered chronologically for the context window.
     */
    public function getRecentMessagesAttribute()
    {
        return $this->messages()->latest()->limit(6)->get()->reverse()->values();
    }
}
