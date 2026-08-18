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
        Schema::create('pengajuan_ktp_riwayat', function (Blueprint $table) {
            $table->id();

            // Pengajuan KTP yang memiliki riwayat
            $table->foreignId('pengajuan_ktp_id')
                ->constrained('pengajuan_ktp')
                ->cascadeOnDelete();

            // Status pada saat perubahan
            $table->enum('status', [
                'menunggu_verifikasi',
                'diproses',
                'disetujui',
                'ditolak',
            ]);

            // Catatan saat status berubah
            $table->text('catatan')->nullable();

            // User yang melakukan perubahan status
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_ktp_riwayat');
    }
};