<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'settings',
        'is_public',
        'bio',
        'username',
        'onboarding_completed',
        'xp',
        'level',
        'dashboard_note',
        'daily_affirmation',
        'affirmation_date',
        'last_weekly_summary',
        'last_weekly_summary_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'settings'             => 'array',
            'is_public'            => 'boolean',
            'onboarding_completed' => 'boolean',
            'xp'                   => 'integer',
            'level'                => 'integer',
            'last_weekly_summary'  => 'array',
            'last_weekly_summary_date' => 'date',
        ];
    }
    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    public function habitCategories(): HasMany
    {
        return $this->hasMany(HabitCategory::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'receiver_id');
    }

    public function friends()
    {
        $sent = $this->sentFriendRequests()
            ->where('status', 'accepted')
            ->with('receiver')
            ->get()
            ->pluck('receiver');

        $received = $this->receivedFriendRequests()
            ->where('status', 'accepted')
            ->with('sender')
            ->get()
            ->pluck('sender');

        return $sent->merge($received);
    }

    public function isFriendWith(User $user): bool
    {
        return Friendship::where(function ($q) use ($user) {
            $q->where('sender_id', $this->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $q->where('sender_id', $user->id)->where('receiver_id', $this->id);
        })->where('status', 'accepted')->exists();
    }

    public function hasPendingRequestFrom(User $user): bool
    {
        return Friendship::where('sender_id', $user->id)
            ->where('receiver_id', $this->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function cheers(): HasMany
    {
        return $this->hasMany(Cheer::class);
    }
    public function xpLogs(): HasMany
    {
        return $this->hasMany(XpLog::class);
    }

    public function pomodoroSessions(): HasMany
    {
        return $this->hasMany(PomodoroSession::class);
    }

    /**
     * Channel name for broadcast notifications — matches the existing private user.{id} channel.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return "user.{$this->id}";
    }

    /**
     * AI Conversations for this user
     */
    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }
}

