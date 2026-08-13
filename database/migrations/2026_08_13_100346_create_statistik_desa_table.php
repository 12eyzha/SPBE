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
        Schema::create('statistik_desa', function (Blueprint $table) {
            $table->id();

            // Tahun statistik
            $table->year('tahun')->unique();

            // Statistik kependudukan
            $table->unsignedInteger('total_penduduk')->default(0);
            $table->unsignedInteger('total_kk')->default(0);

            $table->unsignedInteger('total_laki_laki')->default(0);
            $table->unsignedInteger('total_perempuan')->default(0);

            // Statistik penerima bantuan sosial
            $table->unsignedInteger('total_pkh')->default(0);
            $table->unsignedInteger('total_blt_dd')->default(0);
            $table->unsignedInteger('total_bpnt')->default(0);

            // Statistik kelompok usia
            $table->unsignedInteger('usia_0_14')->default(0);
            $table->unsignedInteger('usia_15_64')->default(0);
            $table->unsignedInteger('usia_65_plus')->default(0);

            // Admin / Super Admin yang terakhir memperbarui statistik
            $table->foreignId('updated_by')
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
        Schema::dropIfExists('statistik_desa');
    }
};