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
        Schema::create('pengaduan', function (Blueprint $table) {
            $table->id();

            // User / masyarakat yang mengirim pengaduan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Data yang dikirim pada form
            $table->string('nama', 100);
            $table->string('nomor', 20);
            $table->text('keterangan');

            // Foto bukti bersifat opsional
            $table->string('foto_bukti', 255)->nullable();

            // Status pengaduan
            $table->enum('status', [
                'terkirim',
                'diteruskan',
                'selesai'
            ])->default('terkirim');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan');
    }
};