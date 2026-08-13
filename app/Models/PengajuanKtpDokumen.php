<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanKtpDokumen extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_ktp_dokumen';

    protected $fillable = [
        'pengajuan_ktp_id',
        'jenis_dokumen',
        'file_path',
        'nama_file',
    ];

    public function pengajuanKtp(): BelongsTo
    {
        return $this->belongsTo(PengajuanKtp::class);
    }
}