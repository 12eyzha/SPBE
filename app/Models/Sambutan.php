<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sambutan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'foto',
        'nama',
        'jabatan',
        'text',
        'created_by',
        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | User yang Membuat
    |--------------------------------------------------------------------------
    */

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | User yang Terakhir Memperbarui
    |--------------------------------------------------------------------------
    */

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}