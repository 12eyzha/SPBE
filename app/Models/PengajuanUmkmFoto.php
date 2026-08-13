<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanUmkmFoto extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_umkm_foto';

    protected $fillable = [
        'pengajuan_umkm_id',
        'file_path',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function pengajuanUmkm(): BelongsTo
    {
        return $this->belongsTo(PengajuanUmkm::class);
    }
}