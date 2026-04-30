<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruSalaryRequest extends Model
{
    protected $fillable = [
        'guru_id',
        'requested_by',
        'requested_at',
        'completed_by',
        'completed_at',
        'gaji',
        'elaun',
        'elaun_transit',
        'elaun_lain',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'gaji' => 'decimal:2',
            'elaun' => 'decimal:2',
            'elaun_transit' => 'decimal:2',
            'elaun_lain' => 'decimal:2',
        ];
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
