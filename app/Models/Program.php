<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = [
        'kawasan_id',
        'pasti_id',
        'title',
        'program_date',
        'program_time',
        'location',
        'description',
        'banner_path',
        'require_absence_reason',
        'markah',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'program_date' => 'date',
            'program_time' => 'datetime:H:i',
            'require_absence_reason' => 'boolean',
            'markah' => 'integer',
        ];
    }

    public function kawasan(): BelongsTo
    {
        return $this->belongsTo(Kawasan::class);
    }

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function gurus(): BelongsToMany
    {
        return $this->belongsToMany(Guru::class, 'program_teacher')
            ->withPivot(['program_status_id', 'absence_reason', 'updated_by'])
            ->withTimestamps();
    }

    public function participations(): HasMany
    {
        return $this->hasMany(ProgramParticipation::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_path
            ? '/uploads/' . ltrim($this->banner_path, '/')
            : null;
    }
}
