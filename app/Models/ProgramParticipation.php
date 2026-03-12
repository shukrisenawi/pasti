<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProgramParticipation extends Model
{
    protected $table = 'program_teacher';

    protected $fillable = [
        'program_id',
        'guru_id',
        'program_status_id',
        'absence_reason',
        'updated_by',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProgramStatus::class, 'program_status_id');
    }
}
