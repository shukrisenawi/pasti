<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ProgramStatus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_hadir',
    ];

    protected function casts(): array
    {
        return [
            'is_hadir' => 'boolean',
        ];
    }

    public function participations(): HasMany
    {
        return $this->hasMany(ProgramParticipation::class);
    }
}
