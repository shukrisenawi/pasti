<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveNotice extends Model
{
    protected $fillable = [
        'guru_id',
        'leave_date',
        'leave_until',
        'reason',
        'mc_image_path',
    ];

    protected function casts(): array
    {
        return [
            'leave_date' => 'date',
            'leave_until' => 'date',
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function getMcImageUrlAttribute(): ?string
    {
        return $this->mc_image_path ? '/uploads/'.ltrim($this->mc_image_path, '/') : null;
    }
}
