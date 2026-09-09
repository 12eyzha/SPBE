<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('sambutans', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Primary Key
            |--------------------------------------------------------------------------
            */

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Foto Kepala Desa
            |--------------------------------------------------------------------------
            |
            | Menyimpan path file foto pada storage lokal.
            |
            */

            $table->string('foto');

            /*
            |--------------------------------------------------------------------------
            | Nama Kepala Desa
            |--------------------------------------------------------------------------
            */

            $table->string('nama');

            /*
            |--------------------------------------------------------------------------
            | Jabatan
            |--------------------------------------------------------------------------
            */

            $table->string('jabatan');

            /*
            |--------------------------------------------------------------------------
            | Teks Sambutan
            |--------------------------------------------------------------------------
            */

            $table->text('text');

            /*
            |--------------------------------------------------------------------------
            | Audit User
            |--------------------------------------------------------------------------
            |
            | Menyimpan user yang membuat dan terakhir memperbarui
            | data sambutan.
            |
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Soft Delete
            |--------------------------------------------------------------------------
            |
            | Data tidak langsung dihapus permanen.
            |
            */

            $table->softDeletes();
        });
    }

    /**
     * Balikkan migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('sambutans');
    }
};