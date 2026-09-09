<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaduanRiwayat extends Model
{
    use HasFactory;

    protected $table = 'pengaduan_riwayat';

    protected $fillable = [
        'pengaduan_id',
        'status',
        'catatan',
        'changed_by',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(
            Pengaduan::class,
            'pengaduan_id'
        );
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}