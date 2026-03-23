<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'pasti_id',
        'transaction_date',
        'transaction_type',
        'amount',
        'amount_remark',
        'transaction_remark',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSignedAmountAttribute(): float
    {
        $amount = (float) $this->amount;

        return $this->transaction_type === 'masuk' ? $amount : -$amount;
    }
}
