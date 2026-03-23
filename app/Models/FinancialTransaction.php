<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'pasti_id',
        'financial_transaction_type_id',
        'transaction_date',
        'credit_debit',
        'payment_method',
        'amount',
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

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(FinancialTransactionType::class, 'financial_transaction_type_id');
    }

    public function getSignedAmountAttribute(): float
    {
        $amount = (float) $this->amount;

        if ($this->credit_debit === 'credit') {
            return $amount;
        }

        return -$amount;
    }
}
