<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuruCourseOffer extends Model
{
    protected $fillable = [
        'target_semester',
        'registration_deadline',
        'sent_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'registration_deadline' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(GuruCourseOfferResponse::class, 'guru_course_offer_id');
    }
}
