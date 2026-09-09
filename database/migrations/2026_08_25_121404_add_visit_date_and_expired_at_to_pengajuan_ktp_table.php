<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_ktp', function (Blueprint $table) {
            $table->date('visit_date')
                ->nullable()
                ->after('approved_at');

            $table->timestamp('expired_at')
                ->nullable()
                ->after('visit_date');

            $table->index('visit_date');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_ktp', function (Blueprint $table) {
            $table->dropIndex([
                'pengajuan_ktp_visit_date_index',
            ]);

            $table->dropIndex([
                'pengajuan_ktp_expired_at_index',
            ]);

            $table->dropColumn([
                'visit_date',
                'expired_at',
            ]);
        });
    }
};