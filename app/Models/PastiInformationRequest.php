<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastiInformationRequest extends Model
{
    protected $fillable = [
        'pasti_id',
        'requested_by',
        'requested_at',
        'completed_by',
        'completed_at',
        'jumlah_guru',
        'jumlah_pembantu_guru',
        'murid_lelaki_4_tahun',
        'murid_perempuan_4_tahun',
        'murid_lelaki_5_tahun',
        'murid_perempuan_5_tahun',
        'murid_lelaki_6_tahun',
        'murid_perempuan_6_tahun',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'jumlah_guru' => 'integer',
            'jumlah_pembantu_guru' => 'integer',
            'murid_lelaki_4_tahun' => 'integer',
            'murid_perempuan_4_tahun' => 'integer',
            'murid_lelaki_5_tahun' => 'integer',
            'murid_perempuan_5_tahun' => 'integer',
            'murid_lelaki_6_tahun' => 'integer',
            'murid_perempuan_6_tahun' => 'integer',
        ];
    }

    public function pasti(): BelongsTo
    {
        return $this->belongsTo(Pasti::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
