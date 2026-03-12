<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Kawasan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'dun',
    ];

    public function pastis(): HasMany
    {
        return $this->hasMany(Pasti::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }
}
