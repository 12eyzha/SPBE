<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_sku', function (Blueprint $table) {
            $table->id();

            // Masyarakat yang melakukan pengajuan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Data identitas pemohon
            $table->string('nik', 16);
            $table->string('nama_lengkap', 100);
            $table->string('nomor_kk', 16);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);

            // Alamat pemohon
            $table->text('alamat');
            $table->string('rt', 5);
            $table->string('rw', 5);
            $table->string('kode_pos', 5);

            // Data usaha
            $table->string('nama_usaha', 150);
            $table->string('jenis_usaha', 100);
            $table->text('deskripsi_usaha');
            $table->text('alamat_usaha');

            // Status pengajuan
            $table->enum('status', [
                'menunggu_verifikasi',
                'disetujui',
                'ditolak',
            ])->default('menunggu_verifikasi');

            // Catatan dari Admin / Super Admin
            $table->text('catatan_admin')->nullable();

            // Nomor antrian setelah disetujui
            $table->string('no_antrian', 20)
                ->nullable()
                ->unique();

            // Admin / Super Admin yang melakukan approval
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Waktu approval
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_sku');
    }
};