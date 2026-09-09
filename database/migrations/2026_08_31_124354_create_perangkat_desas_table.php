<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perangkat_desa', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identitas Perangkat
            |--------------------------------------------------------------------------
            */

            $table->string('nama', 150);

            $table->string('jabatan', 150);

            /*
            |--------------------------------------------------------------------------
            | Foto
            |--------------------------------------------------------------------------
            |
            | Disimpan sebagai path relatif di private/local storage.
            |
            | Contoh:
            | perangkat-desa/abc123.jpg
            |
            */

            $table->string('foto')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Urutan Tampil
            |--------------------------------------------------------------------------
            |
            | Nilai lebih kecil tampil lebih dahulu.
            |
            */

            $table->unsignedInteger('urutan')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | true  = masih aktif / ditampilkan
            | false = tidak aktif / tidak ditampilkan
            |
            */

            $table->boolean('is_active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            |
            | Menyimpan admin terakhir yang mengubah data.
            |
            */

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index([
                'is_active',
                'urutan',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perangkat_desa');
    }
};