<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_umkm', function (Blueprint $table) {
            $table->id();

            // Masyarakat yang mengajukan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Informasi produk
            $table->string('nama_produk', 150);

            $table->foreignId('kategori_id')
                ->constrained('kategori_umkm')
                ->restrictOnDelete();

            $table->text('deskripsi_produk');

            // Kisaran harga produk
            $table->decimal('harga_min', 15, 2);
            $table->decimal('harga_max', 15, 2);

            // Lokasi usaha
            $table->text('alamat');

            // Jam operasional
            $table->time('jam_buka_mulai')->nullable();
            $table->time('jam_buka_selesai')->nullable();

            // Kontak penjual
            $table->string('nomor_wa', 20);

            // Link e-commerce opsional
            $table->string('link_ecommerce', 255)->nullable();

            // Status verifikasi
            $table->enum('status', [
                'menunggu_verifikasi',
                'disetujui',
                'ditolak'
            ])->default('menunggu_verifikasi');

            // Status operasional UMKM
            $table->enum('status_operasional', [
                'aktif',
                'tidak_beroperasi'
            ])->default('aktif');

            // Catatan admin
            $table->text('catatan_admin')->nullable();

            // Admin / Super Admin yang melakukan approval
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_umkm');
    }
};