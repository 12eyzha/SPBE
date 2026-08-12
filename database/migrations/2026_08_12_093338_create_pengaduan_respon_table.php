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
        Schema::create('pengaduan_respon', function (Blueprint $table) {
            $table->id();

            // Pengaduan yang diberi respons
            $table->foreignId('pengaduan_id')
                ->constrained('pengaduan')
                ->cascadeOnDelete();

            // Admin / Super Admin yang memberikan respons
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('respon');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan_respon');
    }
};