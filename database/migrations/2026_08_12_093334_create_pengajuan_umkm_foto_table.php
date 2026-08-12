<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_umkm_foto', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengajuan_umkm_id')
                ->constrained('pengajuan_umkm')
                ->cascadeOnDelete();

            $table->string('file_path', 255);

            // Urutan foto 1 sampai 5
            $table->unsignedTinyInteger('urutan');

            $table->timestamps();

            $table->unique([
                'pengajuan_umkm_id',
                'urutan'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_umkm_foto');
    }
};