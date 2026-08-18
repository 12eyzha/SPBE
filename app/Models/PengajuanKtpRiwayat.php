<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanKtpRiwayat extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_ktp_riwayat';

    protected $fillable = [
        'pengajuan_ktp_id',
        'status',
        'catatan',
        'changed_by',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanKtp::class, 'pengajuan_ktp_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}