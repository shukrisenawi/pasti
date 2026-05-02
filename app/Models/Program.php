<?php

namespace App\Models;

use Carbon\Carbon;
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
            'require_absence_reason' => 'boolean',
            'markah' => 'integer',
        ];
    }

    public function getProgramTimeAttribute($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, (string) $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse((string) $value);
    }

    public function setProgramTimeAttribute($value): void
    {
        if (blank($value)) {
            $this->attributes['program_time'] = null;

            return;
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $this->attributes['program_time'] = Carbon::createFromFormat($format, (string) $value)->format('H:i:s');

                return;
            } catch (\Throwable) {
                continue;
            }
        }

        $this->attributes['program_time'] = Carbon::parse((string) $value)->format('H:i:s');
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
            ->withPivot([
                'program_status_id',
                'absence_reason',
                'absence_reason_status',
                'absence_reason_reviewed_by',
                'absence_reason_reviewed_at',
                'updated_by',
            ])
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
