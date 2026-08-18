<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanSkuRiwayat extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_sku_riwayat';

    protected $fillable = [
        'pengajuan_sku_id',
        'status',
        'catatan',
        'changed_by',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanSku::class, 'pengajuan_sku_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}