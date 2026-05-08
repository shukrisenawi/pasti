<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShirtPurchase extends Model
{
    public const SIZE_OPTIONS = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'];

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'created_by',
        'sent_to_n8n_at',
        'last_broadcast_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_to_n8n_at' => 'datetime',
            'last_broadcast_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ShirtPurchaseResponse::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? '/uploads/' . ltrim($this->image_path, '/')
            : null;
    }
}
