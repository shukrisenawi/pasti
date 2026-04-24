<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\AdminMessage;
use App\Models\AdminMessageReply;
use App\Models\Announcement;
use App\Notifications\AdminMessageReceivedNotification;
use App\Notifications\AdminMessageReplyNotification;
use App\Models\FcmToken;
use App\Support\NameFormatter;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nama_samaran',
        'tarikh_lahir',
        'tarikh_exp_skim_pas',
        'email',
        'locale',
        'admin_assignment_scope',
        'avatar_path',
        'force_password_change',
        'last_login_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tarikh_lahir' => 'date',
            'tarikh_exp_skim_pas' => 'date',
            'force_password_change' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }

    public function assignedPastis(): BelongsToMany
    {
        return $this->belongsToMany(Pasti::class, 'admin_pasti')->withTimestamps();
    }

    public function sentAdminMessages(): HasMany
    {
        return $this->hasMany(AdminMessage::class, 'sender_id');
    }

    public function receivedAdminMessages(): BelongsToMany
    {
        return $this->belongsToMany(AdminMessage::class, 'admin_message_recipients')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }

    public function ajkPositions(): BelongsToMany
    {
        return $this->belongsToMany(AjkPosition::class, 'user_ajk_positions')
            ->withPivot(['assigned_by'])
            ->withTimestamps();
    }

    public function adminMessageReplies(): HasMany
    {
        return $this->hasMany(AdminMessageReply::class, 'sender_id');
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    public function announcements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_user')
            ->withTimestamps();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? '/uploads/'.ltrim($this->avatar_path, '/')
            : '/images/default-avatar.svg';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nama_samaran ?: $this->name;
    }

    public function getNameAttribute(?string $value): ?string
    {
        return NameFormatter::standardize($value);
    }

    public function getNamaSamaranAttribute(?string $value): ?string
    {
        return NameFormatter::standardize($value);
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = NameFormatter::standardize($value);
    }

    public function setNamaSamaranAttribute(?string $value): void
    {
        $this->attributes['nama_samaran'] = NameFormatter::standardize($value);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function getPendingClaimsCountAttribute(): int
    {
        if ($this->hasRole('master_admin')) {
            return Claim::where('status', 'pending')->count();
        }

        if ($this->hasRole('admin')) {
            $assignedPastiIds = $this->assignedPastis()->pluck('pastis.id')->all();
            if (empty($assignedPastiIds)) {
                return 0;
            }

            return Claim::where('status', 'pending')
                ->whereIn('pasti_id', $assignedPastiIds)
                ->count();
        }

        return 0;
    }

    public function unreadInboxMessagesCount(): int
    {
        $unreadRecipientMessageIds = $this->receivedAdminMessages()
            ->wherePivotNull('read_at')
            ->pluck('admin_messages.id');

        $unreadNotificationMessageIds = $this->unreadNotifications()
            ->whereIn('type', [
                AdminMessageReceivedNotification::class,
                AdminMessageReplyNotification::class,
            ])
            ->get()
            ->pluck('data.admin_message_id');

        return $unreadRecipientMessageIds
            ->merge($unreadNotificationMessageIds)
            ->filter()
            ->unique()
            ->count();
    }

    /**
     * @return array<int, string>
     */
    public function routeNotificationForFcm(): array
    {
        return $this->fcmTokens->pluck('token')
            ->filter(fn ($token) => is_string($token) && $token !== '')
            ->values()
            ->all();
    }
}
