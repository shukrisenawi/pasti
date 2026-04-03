<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruCourseOfferResponse extends Model
{
    protected $fillable = [
        'guru_course_offer_id',
        'guru_id',
        'user_id',
        'decision',
        'stop_reason',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(GuruCourseOffer::class, 'guru_course_offer_id');
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
