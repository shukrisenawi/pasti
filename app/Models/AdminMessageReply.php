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
        'deleted_by_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AdminMessage::class, 'admin_message_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function canBeDeletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isDeleted()) {
            return false;
        }

        return (int) $this->sender_id === (int) $user->id
            || $user->hasAnyRole(['master_admin', 'admin']);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function deletedNotice(): string
    {
        return (int) $this->deleted_by_id === (int) $this->sender_id
            ? 'Chat ini telah dipadam oleh owner'
            : 'Chat ini telah dipadam oleh admin';
    }

    public function displayBody(): string
    {
        return $this->isDeleted()
            ? $this->deletedNotice()
            : trim((string) $this->body);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->isDeleted() || ! $this->is_image_attachment) {
            return null;
        }

        return $this->image_path
            ? '/uploads/'.ltrim($this->image_path, '/')
            : null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->isDeleted()) {
            return null;
        }

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
