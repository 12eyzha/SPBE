<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengaduan extends Model
{
    use HasFactory;

    protected $table = 'pengaduan';

    protected $fillable = [
        'user_id',
        'nama',
        'nomor',
        'subjek',
        'keterangan',
        'lokasi',
        'rt',
        'rw',
        'foto_bukti',
        'status',
    ];

    /**
     * User yang membuat pengaduan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * Respons dari Admin / Super Admin.
     */
    public function respon(): HasMany
    {
        return $this->hasMany(
            PengaduanRespon::class
        );
    }

    /**
     * Dokumen pendukung pengaduan.
     */
    public function dokumen(): HasMany
    {
        return $this->hasMany(
            PengaduanDokumen::class
        );
    }

    /**
     * Riwayat perubahan status pengaduan.
     */
    public function riwayat(): HasMany
    {
        return $this->hasMany(
            PengaduanRiwayat::class,
            'pengaduan_id'
        );
    }
}