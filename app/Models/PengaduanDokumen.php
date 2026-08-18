<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaduanDokumen extends Model
{
    use HasFactory;

    protected $table = 'pengaduan_dokumen';

    protected $fillable = [
        'pengaduan_id',
        'file_path',
        'nama_file',
    ];

    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class, 'pengaduan_id');
    }
}