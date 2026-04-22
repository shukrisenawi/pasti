<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function participants(): Collection
    {
        $sender = $this->relationLoaded('sender')
            ? $this->getRelation('sender')
            : $this->sender()->first();

        $recipients = $this->relationLoaded('recipients')
            ? $this->getRelation('recipients')
            : $this->recipients()->get();

        return collect([$sender])
            ->filter()
            ->merge($recipients)
            ->unique('id')
            ->values();
    }

    public function isBulkConversation(): bool
    {
        if ($this->sent_to_all) {
            return true;
        }

        $recipientCount = $this->relationLoaded('recipients')
            ? $this->recipients->count()
            : $this->recipients()->count();

        return $recipientCount > 1;
    }

    public function conversationTitleFor(?User $viewer = null): string
    {
        if ($this->isBulkConversation()) {
            return $this->title ?: 'Hebahan';
        }

        if ($viewer) {
            $otherParticipant = $this->participants()
                ->first(fn (User $user) => (int) $user->id !== (int) $viewer->id);

            if ($otherParticipant) {
                return $otherParticipant->display_name;
            }
        }

        return $this->title ?: ($this->sender?->display_name ?? 'Perbualan');
    }

    public function canBeDeletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $this->sender_id === (int) $user->id;
    }

    public function latestPreview(): string
    {
        $lastReply = $this->relationLoaded('replies')
            ? $this->replies->sortByDesc('created_at')->first()
            : $this->replies()->latest('created_at')->first();

        return trim((string) ($lastReply?->body ?: $this->body));
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
