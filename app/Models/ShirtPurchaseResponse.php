<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShirtPurchaseResponse extends Model
{
    protected $fillable = [
        'shirt_purchase_id',
        'guru_id',
        'size',
        'notes',
        'quantity',
        'submitted_at',
        'paid_at',
        'paid_marked_by',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ShirtPurchase::class, 'shirt_purchase_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function paidMarker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_marked_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
