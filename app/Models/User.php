<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'plan',
    ];

    /**
     * Default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'plan' => 'max',
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

    protected static function booted()
    {
        static::saving(function ($user) {
            if ($user->isDirty('xp') && $user->xp < 0) {
                $user->xp = 0;
            }
        });
    }
    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    public function habitCategories(): HasMany
    {
        return $this->hasMany(HabitCategory::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function moodLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MoodLog::class);
    }

    public function todaysMood(): ?MoodLog
    {
        return $this->moodLogs()
            ->whereDate('logged_date', today())
            ->first();
    }

    public function weeklyMoodAverage(): float
    {
        return round($this->moodLogs()->thisWeek()->avg('score') ?? 0, 1);
    }

    public function getMoodStreak(): int
    {
        $streak = 0;
        $date   = today();
        while ($this->moodLogs()->whereDate('logged_date', $date)->exists()) {
            $streak++;
            $date = $date->subDay();
        }
        return $streak;
    }

    public function completions(): HasManyThrough
    {
        return $this->hasManyThrough(Completion::class, Habit::class);
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

    public function cvs(): HasMany
    {
        return $this->hasMany(UserCv::class);
    }

    public function activeCV(): ?UserCv
    {
        return $this->cvs()->where('is_active', true)->latest()->first();
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
    public function aiConversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function aiMessages(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(AiMessage::class, AiConversation::class, 'user_id', 'conversation_id');
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function jobInterviews(): HasMany
    {
        return $this->hasMany(JobInterview::class);
    }

    public function jobContacts(): HasMany
    {
        return $this->hasMany(JobContact::class);
    }

    // Stats helper used by AI context
    public function jobSearchStats(): array
    {
        $apps = clone $this->jobApplications();
        $total = $this->jobApplications()->count();
        $applied = (clone $apps)->where('status', 'applied')->count();
        $interviewing = (clone $apps)->whereIn('status', ['phone_screen', 'interview'])->count();
        $offers = (clone $apps)->where('status', 'offer')->count();
        $rejected = (clone $apps)->where('status', 'rejected')->count();
        $thisWeek = (clone $apps)->where('applied_date', '>=', now()->startOfWeek())->count();
        
        return [
            'total'        => $total,
            'applied'      => $applied,
            'interviewing' => $interviewing,
            'offers'       => $offers,
            'rejected'     => $rejected,
            'this_week'    => $thisWeek,
        ];
    }

    public function isPro(): bool
    {
        return in_array($this->plan, ['pro', 'max']);
    }

    public function isMax(): bool
    {
        return $this->plan === 'max';
    }

    public function isFree(): bool
    {
        return $this->plan === 'free';
    }

    public function planLimits(): array
    {
        return config('plans.' . $this->plan, config('plans.free'));
    }

    public function canUseFeature(string $feature): bool
    {
        return (bool) ($this->planLimits()[$feature] ?? false);
    }

    public function hasReachedLimit(string $feature, int $current): bool
    {
        $limit = $this->planLimits()[$feature] ?? 0;
        if ($limit === -1) return false;
        return $current >= $limit;
    }
    /**
     * Override default verification notification to use our custom code-based system.
     * The code is already sent in the CreateNewUser action or resend method.
     */
    public function sendEmailVerificationNotification(): void
    {
        // No-panel, custom logic handles this.
    }
}

