<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_ktp', function (Blueprint $table) {
            $table->index(
                'user_id',
                'pengajuan_ktp_user_id_index'
            );

            $table->index(
                'status',
                'pengajuan_ktp_status_index'
            );

            $table->index(
                ['user_id', 'status'],
                'pengajuan_ktp_user_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_ktp', function (Blueprint $table) {
            $table->dropIndex(
                'pengajuan_ktp_user_id_index'
            );

            $table->dropIndex(
                'pengajuan_ktp_status_index'
            );

            $table->dropIndex(
                'pengajuan_ktp_user_status_index'
            );
        });
    }
};