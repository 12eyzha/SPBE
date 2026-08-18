<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->role
            && in_array($this->role->name, $roles, true);
    }

    // Pengajuan yang dibuat oleh user
    public function pengajuanKtp(): HasMany
    {
        return $this->hasMany(PengajuanKtp::class);
    }

    public function pengajuanSku(): HasMany
    {
        return $this->hasMany(PengajuanSku::class);
    }

    public function pengajuanUmkm(): HasMany
    {
        return $this->hasMany(PengajuanUmkm::class);
    }

    // Pengaduan yang dibuat oleh user
    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function pengaduanRespon(): HasMany
    {
        return $this->hasMany(PengaduanRespon::class);
    }

    // Approval pengajuan oleh admin / superadmin
    public function approvedKtp(): HasMany
    {
        return $this->hasMany(PengajuanKtp::class, 'approved_by');
    }

    public function approvedSku(): HasMany
    {
        return $this->hasMany(PengajuanSku::class, 'approved_by');
    }

    public function approvedUmkm(): HasMany
    {
        return $this->hasMany(PengajuanUmkm::class, 'approved_by');
    }

    // Riwayat perubahan status
    public function perubahanStatusKtp(): HasMany
    {
        return $this->hasMany(PengajuanKtpRiwayat::class, 'changed_by');
    }

    public function perubahanStatusSku(): HasMany
    {
        return $this->hasMany(PengajuanSkuRiwayat::class, 'changed_by');
    }

    public function perubahanStatusUmkm(): HasMany
    {
        return $this->hasMany(PengajuanUmkmRiwayat::class, 'changed_by');
    }

    // Statistik yang terakhir diperbarui oleh user
    public function statistikDesaDiperbarui(): HasMany
    {
        return $this->hasMany(StatistikDesa::class, 'updated_by');
    }
}