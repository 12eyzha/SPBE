<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanUmkm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_umkm';

    protected $fillable = [
        'user_id',
        'nama_umkm',
        'kategori_id',
        'deskripsi_umkm',
        'harga_min',
        'harga_max',
        'alamat',
        'jam_buka_mulai',
        'jam_buka_selesai',
        'nomor_wa',
        'link_ecommerce',
        'status',
        'is_active',
        'catatan_admin',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'harga_min' => 'decimal:2',
            'harga_max' => 'decimal:2',
            'jam_buka_mulai' => 'datetime:H:i',
            'jam_buka_selesai' => 'datetime:H:i',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriUmkm::class, 'kategori_id');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(PengajuanUmkmFoto::class)
            ->orderBy('urutan');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(PengajuanUmkmRiwayat::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}