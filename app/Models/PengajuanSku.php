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

        /*
        |--------------------------------------------------------------------------
        | Data Pemohon
        |--------------------------------------------------------------------------
        */

        'nik',
        'nama_lengkap',
        'nomor_kk',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',

        /*
        |--------------------------------------------------------------------------
        | Alamat Pemohon
        |--------------------------------------------------------------------------
        */

        'alamat',
        'rt',
        'rw',
        'kode_pos',

        /*
        |--------------------------------------------------------------------------
        | Data Usaha
        |--------------------------------------------------------------------------
        */

        'nama_usaha',
        'jenis_usaha',
        'deskripsi_usaha',
        'alamat_usaha',
        'rt_usaha',
        'rw_usaha',
        'lama_menjalankan_usaha',
        'perkiraan_penghasilan_per_bulan',

        /*
        |--------------------------------------------------------------------------
        | Status Pengajuan
        |--------------------------------------------------------------------------
        */

        'status',
        'catatan_admin',

        /*
        |--------------------------------------------------------------------------
        | Antrean
        |--------------------------------------------------------------------------
        */

        'no_antrian',

        /*
        |--------------------------------------------------------------------------
        | Approval
        |--------------------------------------------------------------------------
        */

        'approved_by',
        'approved_at',

        /*
        |--------------------------------------------------------------------------
        | Jadwal Pelayanan
        |--------------------------------------------------------------------------
        */

        'visit_date',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' =>
                'date',

            'lama_menjalankan_usaha' =>
                'integer',

            'approved_at' =>
                'datetime',

            'visit_date' =>
                'date',

            'expired_at' =>
                'datetime',
        ];
    }

    /**
     * User pemilik pengajuan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * Dokumen pendukung SKU.
     */
    public function dokumen(): HasMany
    {
        return $this->hasMany(
            PengajuanSkuDokumen::class
        );
    }

    /**
     * Riwayat perubahan status SKU.
     */
    public function riwayat(): HasMany
    {
        return $this->hasMany(
            PengajuanSkuRiwayat::class
        );
    }

    /**
     * Admin / Super Admin yang menyetujui pengajuan.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }
}