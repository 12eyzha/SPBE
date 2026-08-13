<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatistikDesa extends Model
{
    use HasFactory;

    protected $table = 'statistik_desa';

    protected $fillable = [
        'tahun',
        'total_penduduk',
        'total_kk',
        'total_laki_laki',
        'total_perempuan',
        'total_pkh',
        'total_blt_dd',
        'total_bpnt',
        'usia_0_14',
        'usia_15_64',
        'usia_65_plus',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'total_penduduk' => 'integer',
            'total_kk' => 'integer',
            'total_laki_laki' => 'integer',
            'total_perempuan' => 'integer',
            'total_pkh' => 'integer',
            'total_blt_dd' => 'integer',
            'total_bpnt' => 'integer',
            'usia_0_14' => 'integer',
            'usia_15_64' => 'integer',
            'usia_65_plus' => 'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}