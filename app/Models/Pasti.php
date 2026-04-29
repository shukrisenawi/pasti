<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Pasti extends Model
{
    protected $fillable = [
        'kawasan_id',
        'name',
        'code',
        'address',
        'phone',
        'manager_name',
        'manager_phone',
        'image_path',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? '/uploads/' . ltrim($this->image_path, '/')
            : null;
    }

    public function kawasan(): BelongsTo
    {
        return $this->belongsTo(Kawasan::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_pasti')->withTimestamps();
    }

    public function gurus(): HasMany
    {
        return $this->hasMany(Guru::class);
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function informationRequests(): HasMany
    {
        return $this->hasMany(PastiInformationRequest::class);
    }

    public function latestInformationRequest(): HasOne
    {
        return $this->hasOne(PastiInformationRequest::class)->latestOfMany();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PastiScore::class);
    }

    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
