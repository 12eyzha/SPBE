<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanKtp extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_ktp';

    protected $fillable = [
        'user_id',
        'jenis_permohonan',
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
        'keperluan',
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
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(PengajuanKtpDokumen::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(PengajuanKtpRiwayat::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}