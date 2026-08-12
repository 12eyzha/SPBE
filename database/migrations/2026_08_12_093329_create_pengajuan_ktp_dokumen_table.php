<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_ktp_dokumen', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengajuan_ktp_id')
                ->constrained('pengajuan_ktp')
                ->cascadeOnDelete();

            // Jenis dokumen:
            // kk
            // akta_kelahiran
            // ijazah
            // pengantar_rt_rw
            // ktp_lama
            // surat_kehilangan_polsek
            $table->string('jenis_dokumen', 50);

            $table->string('file_path', 255);
            $table->string('nama_file', 255);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_ktp_dokumen');
    }
};