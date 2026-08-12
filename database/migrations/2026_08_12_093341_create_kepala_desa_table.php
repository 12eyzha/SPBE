<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kepala_desa', function (Blueprint $table) {
            $table->id();

            $table->string('nama', 100);
            $table->string('foto', 255)->nullable();

            $table->date('periode_mulai')->nullable();
            $table->date('periode_selesai')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kepala_desa');
    }
};