<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DirectoryFile extends Model
{
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
}

