<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanSku extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_sku';

    protected $fillable = [
        'user_id',
        'nik',
        'nama_lengkap',
        'nomor_kk',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'rt',
        'rw',
        'kode_pos',
        'nama_usaha',
        'jenis_usaha',
        'deskripsi_usaha',
        'alamat_usaha',
        'rt_usaha',
        'rw_usaha',
        'lama_menjalankan_usaha',
        'perkiraan_penghasilan_per_bulan',
        'status',
        'catatan_admin',
        'no_antrian',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'lama_menjalankan_usaha' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(PengajuanSkuDokumen::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(PengajuanSkuRiwayat::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}