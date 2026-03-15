<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\AdminMessage;
use App\Models\AdminMessageReply;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nama_samaran',
        'tarikh_lahir',
        'email',
        'locale',
        'avatar_path',
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

    public function adminMessageReplies(): HasMany
    {
        return $this->hasMany(AdminMessageReply::class, 'sender_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? '/storage/'.ltrim($this->avatar_path, '/')
            : '/images/default-avatar.svg';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nama_samaran ?: $this->name;
    }
}
