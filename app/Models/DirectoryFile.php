<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DirectoryFile extends Model
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'];

    protected $fillable = [
        'title',
        'original_name',
        'file_path',
        'target_type',
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Guru::class, 'directory_file_guru')
            ->withTimestamps();
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! filled($this->file_path)) {
            return null;
        }

        return '/uploads/' . ltrim((string) $this->file_path, '/');
    }

    public function getIsImageAttachmentAttribute(): bool
    {
        $extension = strtolower((string) pathinfo((string) $this->original_name, PATHINFO_EXTENSION));

        return in_array($extension, self::IMAGE_EXTENSIONS, true);
    }
}
