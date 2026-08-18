<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanUmkmRiwayat extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_umkm_riwayat';

    protected $fillable = [
        'pengajuan_umkm_id',
        'status',
        'catatan',
        'changed_by',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanUmkm::class, 'pengajuan_umkm_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}