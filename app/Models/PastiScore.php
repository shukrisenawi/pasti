<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastiScore extends Model
{
    protected $fillable = [
        'pasti_id',
        'pemarkahan_title_option_id',
        'year',
        'score',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'score' => 'decimal:2',
        ];
    }

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function titleOption(): BelongsTo
    {
        return $this->belongsTo(PemarkahanTitleOption::class, 'pemarkahan_title_option_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
