<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EXPIRED_AFTER_DAYS = 7;

    private const SERVICE_END_TIME = '13:00';

    public function up(): void
    {
        DB::table('pengajuan_ktp')
            ->where('status', 'disetujui')
            ->whereNotNull('approved_at')
            ->where(function ($query) {
                $query
                    ->whereNull('visit_date')
                    ->orWhereNull('expired_at');
            })
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $approvedAt = Carbon::parse(
                        $row->approved_at
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Cari hari kerja berikutnya
                    |--------------------------------------------------------------------------
                    */

                    $visitDate =
                        $row->visit_date !== null
                            ? Carbon::parse(
                                $row->visit_date
                            )
                            : $this->getNextWorkingDay(
                                $approvedAt
                            );

                    /*
                    |--------------------------------------------------------------------------
                    | Hitung expired
                    |--------------------------------------------------------------------------
                    */

                    $expiredAt =
                        $row->expired_at !== null
                            ? Carbon::parse(
                                $row->expired_at
                            )
                            : $this->calculateExpiredAt(
                                $visitDate
                            );

                    DB::table('pengajuan_ktp')
                        ->where('id', $row->id)
                        ->update([
                            'visit_date' =>
                                $visitDate->format(
                                    'Y-m-d'
                                ),

                            'expired_at' =>
                                $expiredAt->format(
                                    'Y-m-d H:i:s'
                                ),

                            'updated_at' =>
                                now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Jangan menghapus data secara agresif.
        |--------------------------------------------------------------------------
        |
        | Migration ini bersifat backfill untuk data lama.
        | Tidak ada cara aman untuk membedakan data yang diisi
        | migration ini dengan data yang sudah ada sebelumnya.
        |
        | Karena itu rollback sengaja dikosongkan.
        |
        */
    }

    private function getNextWorkingDay(
        Carbon $approvedAt
    ): Carbon {
        $date = $approvedAt
            ->copy()
            ->startOfDay()
            ->addDay();

        while (
            $date->isSaturday() ||
            $date->isSunday()
        ) {
            $date->addDay();
        }

        return $date;
    }

    private function calculateExpiredAt(
        Carbon $visitDate
    ): Carbon {
        return $visitDate
            ->copy()
            ->addDays(
                self::EXPIRED_AFTER_DAYS
            )
            ->setTimeFromTimeString(
                self::SERVICE_END_TIME
            );
    }
};
