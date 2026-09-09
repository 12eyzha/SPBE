<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan jadwal kunjungan dan masa berlaku
     * ke pengajuan SKU.
     */
    public function up(): void
    {
        Schema::table('pengajuan_sku', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Tanggal Kunjungan
            |--------------------------------------------------------------------------
            |
            | Diisi ketika pengajuan SKU disetujui.
            | Format database: DATE
            |
            */

            $table->date('visit_date')
                ->nullable()
                ->after('approved_at');

            /*
            |--------------------------------------------------------------------------
            | Waktu Expired
            |--------------------------------------------------------------------------
            |
            | Menentukan batas akhir masa berlaku pengajuan.
            | Format database: TIMESTAMP
            |
            */

            $table->timestamp('expired_at')
                ->nullable()
                ->after('visit_date');
        });
    }

    /**
     * Menghapus kolom yang ditambahkan.
     */
    public function down(): void
    {
        Schema::table('pengajuan_sku', function (Blueprint $table) {
            $table->dropColumn([
                'visit_date',
                'expired_at',
            ]);
        });
    }
};
