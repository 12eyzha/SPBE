<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_ktp', function (Blueprint $table) {
            $table->id();

            // Masyarakat yang mengajukan
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Jenis pengurusan KTP
            $table->enum('jenis_permohonan', [
                'baru',
                'hilang',
                'perpanjangan',
            ]);

            // Data pemohon
            // NIK merupakan data pribadi sensitif dan harus dibatasi aksesnya.
            $table->string('nik', 16);

            $table->string('nama_lengkap', 100);
            $table->string('nomor_kk', 16);

            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);

            // Alamat
            $table->text('alamat');
            $table->string('rt', 5);
            $table->string('rw', 5);
            $table->string('kode_pos', 5);

            // Keperluan
            $table->string('keperluan', 150);

            // Status pengajuan
            $table->enum('status', [
                'menunggu_verifikasi',
                'diproses',
                'disetujui',
                'ditolak',
            ])->default('menunggu_verifikasi');

            // Catatan Admin / Super Admin
            $table->text('catatan_admin')->nullable();

            // Nomor antrian setelah disetujui
            $table->string('no_antrian', 20)
                ->nullable()
                ->unique();

            // User admin/superadmin yang melakukan approval
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
        Schema::dropIfExists('pengajuan_ktp');
    }
};