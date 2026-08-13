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

    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    public function pengaduanRespon(): HasMany
    {
        return $this->hasMany(PengaduanRespon::class);
    }
}