<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriUmkm extends Model
{
    use HasFactory;

    protected $table = 'kategori_umkm';

    protected $fillable = [
        'nama',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pengajuanUmkm(): HasMany
    {
        return $this->hasMany(PengajuanUmkm::class, 'kategori_id');
    }
}