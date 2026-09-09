<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengajuanSkuStatusRequest;
use App\Http\Resources\AdminPengajuanSkuResource;
use App\Models\PengajuanSku;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AdminPengajuanSkuController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Pelayanan
    |--------------------------------------------------------------------------
    */

    /**
     * Jam mulai pelayanan desa.
     *
     * Digunakan sebagai awal waktu pelayanan
     * pada tanggal kunjungan.
     */
    private const SERVICE_START_TIME = '08:30';

    /**
     * Jam selesai pelayanan desa.
     *
     * Digunakan sebagai batas waktu expired.
     */
    private const SERVICE_END_TIME = '13:00';

    /**
     * Masa berlaku pengajuan setelah tanggal kunjungan.
     */
    private const EXPIRED_AFTER_DAYS = 7;

    /*
    |--------------------------------------------------------------------------
    | Status Workflow
    |--------------------------------------------------------------------------
    */

    /**
     * Status workflow pengajuan SKU.
     *
     * menunggu_verifikasi
     *        ↓
     *     diproses
     *      ↙   ↘
     * disetujui  ditolak
     *
     * disetujui dan ditolak merupakan status final.
     */
    private const ALLOWED_TRANSITIONS = [
        'menunggu_verifikasi' => [
            'menunggu_verifikasi',
            'diproses',
            'disetujui',
            'ditolak',
        ],

        'diproses' => [
            'diproses',
            'disetujui',
            'ditolak',
        ],

        'disetujui' => [
            'disetujui',
        ],

        'ditolak' => [
            'ditolak',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan daftar seluruh pengajuan SKU
     * untuk Admin / Super Admin.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanSku::query()
            ->with([
                'user',

                'dokumen',

                'riwayat' => fn ($query) =>
                    $query->latest(),

                'approvedBy',
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' =>
                'Daftar pengajuan SKU berhasil diambil.',

            'data' =>
                AdminPengajuanSkuResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan detail pengajuan SKU
     * untuk Admin / Super Admin.
     */
    public function show(
        PengajuanSku $pengajuanSku
    ): JsonResponse {
        $pengajuanSku->load([
            'user',

            'dokumen',

            'riwayat.changedBy',

            'approvedBy',
        ]);

        return response()->json([
            'message' =>
                'Detail pengajuan SKU berhasil diambil.',

            'data' =>
                new AdminPengajuanSkuResource(
                    $pengajuanSku
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Status
    |--------------------------------------------------------------------------
    */

    /**
     * Memperbarui status pengajuan SKU.
     *
     * Saat disetujui:
     *
     * - approved_by diisi
     * - approved_at diisi
     * - no_antrian dibuat
     * - visit_date dihitung pada hari kerja berikutnya
     * - pelayanan dimulai pukul 08:30
     * - pelayanan berakhir pukul 13:00
     * - expired_at dihitung 7 hari setelah visit_date
     */
    public function updateStatus(
        UpdatePengajuanSkuStatusRequest $request,
        PengajuanSku $pengajuanSku,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengajuanSku =
                DB::transaction(
                    function () use (
                        $request,
                        $pengajuanSku,
                        $auditLogService
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock Record
                        |--------------------------------------------------------------------------
                        |
                        | Mencegah dua Admin / Super Admin memproses
                        | pengajuan yang sama secara bersamaan.
                        |
                        */

                        $pengajuanSku =
                            PengajuanSku::query()
                                ->whereKey(
                                    $pengajuanSku->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        $validated =
                            $request->validated();

                        $statusLama =
                            $pengajuanSku->status;

                        $statusBaru =
                            $validated['status'];

                        $catatanLama =
                            $pengajuanSku->catatan_admin;

                        $catatanBaru =
                            array_key_exists(
                                'catatan_admin',
                                $validated
                            )
                                ? $validated['catatan_admin']
                                : null;

                        /*
                        |--------------------------------------------------------------------------
                        | Transition Check
                        |--------------------------------------------------------------------------
                        */

                        $allowedTransitions =
                            self::ALLOWED_TRANSITIONS[
                                $statusLama
                            ] ?? [];

                        if (
                            ! in_array(
                                $statusBaru,
                                $allowedTransitions,
                                true
                            )
                        ) {
                            throw new HttpException(
                                422,
                                "Status pengajuan tidak dapat diubah dari {$statusLama} menjadi {$statusBaru}."
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Catatan Wajib Jika Ditolak
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $statusBaru === 'ditolak' &&
                            blank($catatanBaru)
                        ) {
                            throw new HttpException(
                                422,
                                'Catatan wajib diisi ketika pengajuan ditolak.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Nilai Lama
                        |--------------------------------------------------------------------------
                        */

                        $approvedByLama =
                            $pengajuanSku->approved_by;

                        $approvedAtLama =
                            $pengajuanSku->approved_at;

                        $noAntrianLama =
                            $pengajuanSku->no_antrian;

                        $visitDateLama =
                            $pengajuanSku->visit_date;

                        $expiredAtLama =
                            $pengajuanSku->expired_at;

                        /*
                        |--------------------------------------------------------------------------
                        | Default Update
                        |--------------------------------------------------------------------------
                        |
                        | Untuk status selain disetujui,
                        | informasi approval dibersihkan.
                        |
                        */

                        $data = [
                            'status' =>
                                $statusBaru,

                            'catatan_admin' =>
                                $catatanBaru,

                            'approved_by' =>
                                null,

                            'approved_at' =>
                                null,

                            'no_antrian' =>
                                null,

                            'visit_date' =>
                                null,

                            'expired_at' =>
                                null,
                        ];

                        /*
                        |--------------------------------------------------------------------------
                        | Jika Disetujui
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $statusBaru === 'disetujui'
                        ) {
                            /*
                            |--------------------------------------------------------------------------
                            | Waktu Approval
                            |--------------------------------------------------------------------------
                            */

                            $approvedAt =
                                $pengajuanSku->approved_at;

                            if (
                                ! $approvedAt
                            ) {
                                $approvedAt =
                                    now();
                            } else {
                                $approvedAt =
                                    $this->toCarbon(
                                        $approvedAt
                                    );
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Tanggal Kunjungan
                            |--------------------------------------------------------------------------
                            |
                            | Jika belum memiliki visit_date,
                            | gunakan hari kerja berikutnya.
                            |
                            */

                            $visitDate =
                                $pengajuanSku->visit_date;

                            if (
                                ! $visitDate
                            ) {
                                $visitDate =
                                    $this->getNextWorkingDay(
                                        $approvedAt
                                    );
                            } else {
                                $visitDate =
                                    $this->toCarbon(
                                        $visitDate
                                    );
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Waktu Mulai Pelayanan
                            |--------------------------------------------------------------------------
                            |
                            | visit_date disimpan sebagai DATE.
                            | Karena itu jam tidak disimpan ke kolom database.
                            |
                            | SERVICE_START_TIME tetap digunakan sebagai
                            | titik awal jadwal pelayanan.
                            |
                            */

                            $visitDateTime =
                                $visitDate
                                    ->copy()
                                    ->setTimeFromTimeString(
                                        self::SERVICE_START_TIME
                                    );

                            /*
                            |--------------------------------------------------------------------------
                            | Expired At
                            |--------------------------------------------------------------------------
                            |
                            | Expired dihitung 7 hari setelah tanggal kunjungan
                            | dan berakhir pada pukul 13:00.
                            |
                            */

                            $expiredAt =
                                $pengajuanSku->expired_at;

                            if (
                                ! $expiredAt
                            ) {
                                $expiredAt =
                                    $this->calculateExpiredAt(
                                        $visitDateTime
                                    );
                            } else {
                                $expiredAt =
                                    $this->toCarbon(
                                        $expiredAt
                                    );
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Simpan Approval
                            |--------------------------------------------------------------------------
                            */

                            $data[
                                'approved_by'
                            ] =
                                $pengajuanSku->approved_by
                                ??
                                $request
                                    ->user()
                                    ->id;

                            $data[
                                'approved_at'
                            ] =
                                $approvedAt;

                            /*
                            |--------------------------------------------------------------------------
                            | Nomor Antrean
                            |--------------------------------------------------------------------------
                            */

                            $data[
                                'no_antrian'
                            ] =
                                $pengajuanSku->no_antrian
                                ?:
                                $this->generateNomorAntrian(
                                    $approvedAt
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | Tanggal Kunjungan
                            |--------------------------------------------------------------------------
                            |
                            | Database menerima DATE,
                            | sehingga hanya tanggal yang disimpan.
                            |
                            */

                            $data[
                                'visit_date'
                            ] =
                                $visitDateTime
                                    ->startOfDay();

                            /*
                            |--------------------------------------------------------------------------
                            | Masa Berlaku
                            |--------------------------------------------------------------------------
                            */

                            $data[
                                'expired_at'
                            ] =
                                $expiredAt;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Normalisasi Nilai Lama / Baru
                        |--------------------------------------------------------------------------
                        */

                        $visitDateLamaValue =
                            $this->normalizeDateValue(
                                $visitDateLama
                            );

                        $visitDateBaruValue =
                            $this->normalizeDateValue(
                                $data['visit_date']
                            );

                        $expiredAtLamaValue =
                            $this->normalizeDateTimeValue(
                                $expiredAtLama
                            );

                        $expiredAtBaruValue =
                            $this->normalizeDateTimeValue(
                                $data['expired_at']
                            );

                        $approvedAtLamaValue =
                            $this->normalizeDateTimeValue(
                                $approvedAtLama
                            );

                        $approvedAtBaruValue =
                            $this->normalizeDateTimeValue(
                                $data['approved_at']
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Deteksi Perubahan
                        |--------------------------------------------------------------------------
                        */

                        $hasChanges =
                            $statusLama !==
                                $statusBaru ||

                            $catatanLama !==
                                $catatanBaru ||

                            $approvedByLama !==
                                $data['approved_by'] ||

                            $approvedAtLamaValue !==
                                $approvedAtBaruValue ||

                            $noAntrianLama !==
                                $data['no_antrian'] ||

                            $visitDateLamaValue !==
                                $visitDateBaruValue ||

                            $expiredAtLamaValue !==
                                $expiredAtBaruValue;

                        /*
                        |--------------------------------------------------------------------------
                        | Tidak Ada Perubahan
                        |--------------------------------------------------------------------------
                        */

                        if (
                            ! $hasChanges
                        ) {
                            return $pengajuanSku
                                ->fresh([
                                    'user',

                                    'dokumen',

                                    'riwayat' =>
                                        fn ($query) =>
                                            $query->latest(),

                                    'approvedBy',
                                ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Update Database
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanSku->update(
                            $data
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Riwayat Status
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanSku
                            ->riwayat()
                            ->create([
                                'status' =>
                                    $statusBaru,

                                'catatan' =>
                                    $catatanBaru,

                                'changed_by' =>
                                    $request
                                        ->user()
                                        ->id,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Audit Log
                        |--------------------------------------------------------------------------
                        |
                        | Tidak mencatat:
                        | - NIK
                        | - Nomor KK
                        | - Isi dokumen
                        | - Password
                        |
                        */

                        $auditLogService->record(
                            $request,
                            'update_status',
                            'pengajuan_sku',
                            "Status pengajuan SKU #{$pengajuanSku->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                            $pengajuanSku,
                            [
                                'status' =>
                                    $statusLama,

                                'catatan_admin' =>
                                    $catatanLama,

                                'approved_by' =>
                                    $approvedByLama,

                                'approved_at' =>
                                    $approvedAtLamaValue,

                                'no_antrian' =>
                                    $noAntrianLama,

                                'visit_date' =>
                                    $visitDateLamaValue,

                                'expired_at' =>
                                    $expiredAtLamaValue,
                            ],
                            [
                                'status' =>
                                    $statusBaru,

                                'catatan_admin' =>
                                    $catatanBaru,

                                'approved_by' =>
                                    $data['approved_by'],

                                'approved_at' =>
                                    $approvedAtBaruValue,

                                'no_antrian' =>
                                    $data['no_antrian'],

                                'visit_date' =>
                                    $visitDateBaruValue,

                                'expired_at' =>
                                    $expiredAtBaruValue,
                            ]
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Fresh Response
                        |--------------------------------------------------------------------------
                        */

                        return $pengajuanSku
                            ->fresh([
                                'user',

                                'dokumen',

                                'riwayat' =>
                                    fn ($query) =>
                                        $query->latest(),

                                'approvedBy',
                            ]);
                    }
                );

            return response()->json([
                'message' =>
                    'Status pengajuan SKU berhasil diperbarui.',

                'data' =>
                    new AdminPengajuanSkuResource(
                        $pengajuanSku
                    ),
            ]);
        } catch (
            HttpException $e
        ) {
            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], $e->getStatusCode());
        } catch (
            Throwable $e
        ) {
            report($e);

            return response()->json([
                'message' =>
                    'Status pengajuan SKU gagal diperbarui.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Mendapatkan hari kerja berikutnya.
     *
     * Sabtu dan Minggu dilewati.
     *
     * Jam pelayanan dimulai pukul 08:30.
     */
    private function getNextWorkingDay(
        Carbon $approvedAt
    ): Carbon {
        $date =
            $approvedAt
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

    /**
     * Menghitung waktu expired.
     *
     * Expired pada pukul 13:00,
     * 7 hari setelah tanggal kunjungan.
     *
     * Contoh:
     *
     * Visit:
     * 2026-08-28 08:30
     *
     * Expired:
     * 2026-09-04 13:00
     */
    private function calculateExpiredAt(
        Carbon $visitDate
    ): Carbon {
        return $visitDate
            ->copy()
            ->setTimeFromTimeString(
                self::SERVICE_START_TIME
            )
            ->addDays(
                self::EXPIRED_AFTER_DAYS
            )
            ->setTimeFromTimeString(
                self::SERVICE_END_TIME
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Queue Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Membuat nomor antrean unik.
     *
     * Format:
     *
     * SKU-YYYYMMDD-XXXXX
     */
    private function generateNomorAntrian(
        Carbon $approvedAt
    ): string {
        do {
            $nomor =
                'SKU-' .
                $approvedAt->format(
                    'Ymd'
                ) .
                '-' .
                strtoupper(
                    Str::random(5)
                );
        } while (
            PengajuanSku::query()
                ->where(
                    'no_antrian',
                    $nomor
                )
                ->exists()
        );

        return $nomor;
    }

    /*
    |--------------------------------------------------------------------------
    | Type Normalization Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Mengubah berbagai bentuk nilai tanggal
     * menjadi string Y-m-d.
     */
    private function normalizeDateValue(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (
            $value instanceof Carbon
        ) {
            return $value->format(
                'Y-m-d'
            );
        }

        if (
            $value instanceof \DateTimeInterface
        ) {
            return $value->format(
                'Y-m-d'
            );
        }

        return Carbon::parse(
            $value
        )->format(
            'Y-m-d'
        );
    }

    /**
     * Mengubah berbagai bentuk nilai datetime
     * menjadi string Y-m-d H:i:s.
     */
    private function normalizeDateTimeValue(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (
            $value instanceof Carbon
        ) {
            return $value->format(
                'Y-m-d H:i:s'
            );
        }

        if (
            $value instanceof \DateTimeInterface
        ) {
            return $value->format(
                'Y-m-d H:i:s'
            );
        }

        return Carbon::parse(
            $value
        )->format(
            'Y-m-d H:i:s'
        );
    }

    /**
     * Mengubah nilai menjadi Carbon.
     */
    private function toCarbon(
        mixed $value
    ): Carbon {
        if (
            $value instanceof Carbon
        ) {
            return $value->copy();
        }

        if (
            $value instanceof \DateTimeInterface
        ) {
            return Carbon::instance(
                $value
            );
        }

        return Carbon::parse(
            $value
        );
    }
}