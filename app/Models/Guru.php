<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $fillable = [
        'user_id',
        'pasti_id',
        'name',
        'email',
        'avatar_path',
        'is_assistant',
        'phone',
        'joined_at',
        'active',
        'terima_anugerah',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'active' => 'boolean',
            'is_assistant' => 'boolean',
            'terima_anugerah' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_teacher')
            ->withPivot(['program_status_id', 'updated_by'])
            ->withTimestamps();
    }

    public function kpiSnapshot(): HasOne
    {
        return $this->hasOne(KpiSnapshot::class);
    }

    public function leaveNotices(): HasMany
    {
        return $this->hasMany(LeaveNotice::class);
    }

    public function scopeWithLeaveDaysForYear(Builder $query, int $year, string $alias = 'leave_notices_current_year_count'): Builder
    {
        $yearStart = sprintf('%d-01-01', $year);
        $yearEnd = sprintf('%d-12-31', $year);

        $daysSubquery = LeaveNotice::query()
            ->selectRaw(
                'COALESCE(SUM(CASE
                    WHEN LEAST(COALESCE(leave_until, leave_date), ?) < GREATEST(leave_date, ?) THEN 0
                    ELSE DATEDIFF(LEAST(COALESCE(leave_until, leave_date), ?), GREATEST(leave_date, ?)) + 1
                END), 0)',
                [$yearEnd, $yearStart, $yearEnd, $yearStart]
            )
            ->whereColumn('guru_id', 'gurus.id')
            ->whereDate('leave_date', '<=', $yearEnd)
            ->whereRaw('COALESCE(leave_until, leave_date) >= ?', [$yearStart]);

        return $query
            ->addSelect('gurus.*')
            ->selectSub($daysSubquery, $alias);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->display_name ?? $this->name ?? '-';
    }

    public function getDisplayEmailAttribute(): string
    {
        return $this->user?->email ?? $this->email ?? '-';
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->user?->avatar_url
            ?? ($this->avatar_path ? '/uploads/'.ltrim($this->avatar_path, '/') : '/images/default-avatar.svg');
    }
}
