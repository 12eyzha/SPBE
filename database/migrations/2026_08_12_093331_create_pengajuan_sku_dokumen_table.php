<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_sku_dokumen', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengajuan_sku_id')
                ->constrained('pengajuan_sku')
                ->cascadeOnDelete();

            // Jenis dokumen:
            // ktp
            // kk
            // pengantar_rt_rw
            // foto_tempat_usaha
            $table->string('jenis_dokumen', 50);

            $table->string('file_path', 255);
            $table->string('nama_file', 255);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_sku_dokumen');
    }
};