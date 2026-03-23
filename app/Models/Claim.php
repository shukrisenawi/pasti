<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    protected $fillable = [
        'user_id',
        'guru_id',
        'pasti_id',
        'claim_date',
        'amount',
        'notes',
        'image_path',
        'status',
        'approved_amount',
        'payment_method',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

