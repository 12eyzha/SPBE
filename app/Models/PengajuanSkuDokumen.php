<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanSkuDokumen extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_sku_dokumen';

    protected $fillable = [
        'pengajuan_sku_id',
        'jenis_dokumen',
        'file_path',
        'nama_file',
    ];

    public function pengajuanSku(): BelongsTo
    {
        return $this->belongsTo(PengajuanSku::class);
    }
}