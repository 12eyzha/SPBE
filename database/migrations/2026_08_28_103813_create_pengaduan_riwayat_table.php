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
        Schema::create('pengaduan_riwayat', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Pengaduan
            |--------------------------------------------------------------------------
            |
            | Riwayat ini milik satu pengaduan tertentu.
            |
            */

            $table->foreignId('pengaduan_id')
                ->constrained('pengaduan')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | Status mengikuti workflow Pengaduan:
            |
            | terkirim
            | diteruskan
            | selesai
            |
            */

            $table->enum('status', [
                'terkirim',
                'diteruskan',
                'selesai',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Catatan
            |--------------------------------------------------------------------------
            |
            | Bisa berisi keterangan perubahan status.
            | Misalnya:
            |
            | Pengaduan berhasil dikirim.
            | Pengaduan diteruskan kepada petugas terkait.
            | Pengaduan telah selesai ditangani.
            |
            */

            $table->text('catatan')->nullable();

            /*
            |--------------------------------------------------------------------------
            | User yang Mengubah Status
            |--------------------------------------------------------------------------
            |
            | Bisa merupakan:
            | - user saat membuat pengaduan
            | - admin / superadmin saat memproses
            |
            | Ketika user dihapus, histori tetap dipertahankan.
            |
            */

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            |
            | Mempercepat pengambilan riwayat berdasarkan pengaduan.
            |
            */

            $table->index([
                'pengaduan_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan_riwayat');
    }
};