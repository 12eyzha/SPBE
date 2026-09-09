<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create hero_slides table.
     */
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();

            // ============================================
            // DATA HERO
            // ============================================

            $table->string('image_path', 255);
            $table->string('title', 255);
            $table->text('subtitle')->nullable();

            // ============================================
            // RELASI BERITA
            // NULL = slide welcome / bukan berita
            // ============================================

            $table->foreignId('berita_id')
                ->nullable()
                ->constrained('berita')
                ->nullOnDelete();

            // ============================================
            // PENGATURAN SLIDE
            // ============================================

            $table->unsignedInteger('urutan')->default(1);
            $table->boolean('is_active')->default(true);

            // ============================================
            // AUDIT
            // ============================================

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // ============================================
            // INDEX
            // ============================================

            $table->index([
                'is_active',
                'urutan',
            ]);
        });
    }

    /**
     * Drop hero_slides table.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};