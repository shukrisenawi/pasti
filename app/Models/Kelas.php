<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'pasti_id',
        'name',
    ];

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function studentCount(): HasOne
    {
        return $this->hasOne(KelasStudentCount::class);
    }
}
