<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_message_id',
        'sender_id',
        'body',
        'image_path',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AdminMessage::class, 'admin_message_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->is_image_attachment) {
            return null;
        }

        return $this->image_path
            ? '/storage/'.ltrim($this->image_path, '/')
            : null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->image_path
            ? '/storage/'.ltrim($this->image_path, '/')
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
