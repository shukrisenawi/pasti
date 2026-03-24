<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'title',
        'body',
        'image_path',
        'sent_to_all',
    ];

    protected function casts(): array
    {
        return [
            'sent_to_all' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_message_recipients')
            ->withPivot(['read_at'])
            ->withTimestamps();
    }

    public function recipientLinks(): HasMany
    {
        return $this->hasMany(AdminMessageRecipient::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(AdminMessageReply::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->is_image_attachment) {
            return null;
        }

        return $this->image_path
            ? '/uploads/'.ltrim($this->image_path, '/')
            : null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->image_path
            ? '/uploads/'.ltrim($this->image_path, '/')
            : null;
    }

    public function getAttachmentNameAttribute(): ?string
    {
        return $this->image_path ? basename($this->image_path) : null;
    }

    public function getIsImageAttachmentAttribute(): bool
    {
        if (! $this->image_path) {
            return false;
        }

        $extension = strtolower(pathinfo($this->image_path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}
