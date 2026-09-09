<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengajuanKtpStatusRequest;
use App\Http\Resources\AdminPengajuanKtpResource;
use App\Models\PengajuanKtp;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AdminPengajuanKtpController extends Controller
{
    /**
     * Jam pelayanan desa.
     */
    private const SERVICE_START_TIME = '08:30';

    private const SERVICE_END_TIME = '13:00';

    /**
     * Masa berlaku setelah tanggal kunjungan.
     */
    private const EXPIRED_AFTER_DAYS = 7;

    /**
     * Status workflow pengajuan KTP.
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

    /**
     * Daftar pengajuan KTP.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanKtp::query()
            ->with([
                'user',
                'dokumen',
                'riwayat' => fn ($query) => $query->latest(),
                'approvedBy',
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' =>
                'Daftar pengajuan KTP berhasil diambil.',

            'data' =>
                AdminPengajuanKtpResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /**
     * Detail pengajuan KTP.
     */
    public function show(
        PengajuanKtp $pengajuanKtp
    ): JsonResponse {
        $pengajuanKtp->load([
            'user',
            'dokumen',
            'riwayat.changedBy',
            'approvedBy',
        ]);

        return response()->json([
            'message' =>
                'Detail pengajuan KTP berhasil diambil.',

            'data' =>
                new AdminPengajuanKtpResource(
                    $pengajuanKtp
                ),
        ]);
    }

    /**
     * Update status pengajuan KTP.
     */
    public function updateStatus(
        UpdatePengajuanKtpStatusRequest $request,
        PengajuanKtp $pengajuanKtp,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengajuanKtp = DB::transaction(
                function () use (
                    $request,
                    $pengajuanKtp,
                    $auditLogService
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock Record
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanKtp = PengajuanKtp::query()
                        ->whereKey($pengajuanKtp->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $validated = $request->validated();

                    $statusLama = $pengajuanKtp->status;
                    $statusBaru = $validated['status'];

                    $catatanLama =
                        $pengajuanKtp->catatan_admin;

                    $catatanBaru =
                        array_key_exists(
                            'catatan_admin',
                            $validated
                        )
                            ? $validated['catatan_admin']
                            : null;

                    /*
                    |--------------------------------------------------------------------------
                    | Validasi Transition
                    |--------------------------------------------------------------------------
                    */

                    $allowedTransitions =
                        self::ALLOWED_TRANSITIONS[$statusLama] ?? [];

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
                        $pengajuanKtp->approved_by;

                    $approvedAtLama =
                        $pengajuanKtp->approved_at;

                    $noAntrianLama =
                        $pengajuanKtp->no_antrian;

                    $visitDateLama =
                        $pengajuanKtp->visit_date;

                    $expiredAtLama =
                        $pengajuanKtp->expired_at;

                    /*
                    |--------------------------------------------------------------------------
                    | Normalisasi Nilai Lama
                    |--------------------------------------------------------------------------
                    |
                    | Kita ubah ke string sebelum dibandingkan agar
                    | PHP IntelliSense tidak salah membaca tipe date.
                    |
                    */

                    $approvedAtLamaValue =
                        $approvedAtLama
                            ? Carbon::parse(
                                (string) $approvedAtLama
                            )->toISOString()
                            : null;

                    $visitDateLamaValue =
                        $visitDateLama
                            ? Carbon::parse(
                                (string) $visitDateLama
                            )->format('Y-m-d')
                            : null;

                    $expiredAtLamaValue =
                        $expiredAtLama
                            ? Carbon::parse(
                                (string) $expiredAtLama
                            )->toISOString()
                            : null;

                    /*
                    |--------------------------------------------------------------------------
                    | Default Update
                    |--------------------------------------------------------------------------
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
                    | Approval
                    |--------------------------------------------------------------------------
                    */

                    if ($statusBaru === 'disetujui') {
                        /*
                        |----------------------------------------------------------------------
                        | Approval time
                        |----------------------------------------------------------------------
                        */

                        $approvedAt =
                            $pengajuanKtp->approved_at
                                ?? now();

                        /*
                        |----------------------------------------------------------------------
                        | Hari kerja berikutnya
                        |----------------------------------------------------------------------
                        */

                        $visitDate =
                            $pengajuanKtp->visit_date
                                ?? $this->getNextWorkingDay(
                                    Carbon::parse(
                                        (string) $approvedAt
                                    )
                                );

                        /*
                        |----------------------------------------------------------------------
                        | Expired
                        |----------------------------------------------------------------------
                        */

                        $expiredAt =
                            $pengajuanKtp->expired_at
                                ?? $this->calculateExpiredAt(
                                    Carbon::parse(
                                        (string) $visitDate
                                    )
                                );

                        /*
                        |----------------------------------------------------------------------
                        | Simpan approval
                        |----------------------------------------------------------------------
                        */

                        $data['approved_by'] =
                            $pengajuanKtp->approved_by
                                ?? $request->user()->id;

                        $data['approved_at'] =
                            Carbon::parse(
                                (string) $approvedAt
                            );

                        $data['no_antrian'] =
                            $pengajuanKtp->no_antrian
                                ?: $this->generateNomorAntrian(
                                    Carbon::parse(
                                        (string) $approvedAt
                                    )
                                );

                        $data['visit_date'] =
                            Carbon::parse(
                                (string) $visitDate
                            )->startOfDay();

                        $data['expired_at'] =
                            Carbon::parse(
                                (string) $expiredAt
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalisasi Nilai Baru
                    |--------------------------------------------------------------------------
                    */

                    $approvedAtBaruValue =
                        $data['approved_at']
                            ? Carbon::parse(
                                (string) $data['approved_at']
                            )->toISOString()
                            : null;

                    $visitDateBaruValue =
                        $data['visit_date']
                            ? Carbon::parse(
                                (string) $data['visit_date']
                            )->format('Y-m-d')
                            : null;

                    $expiredAtBaruValue =
                        $data['expired_at']
                            ? Carbon::parse(
                                (string) $data['expired_at']
                            )->toISOString()
                            : null;

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

                    if (! $hasChanges) {
                        return $pengajuanKtp->fresh([
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
                    | Update
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanKtp->update($data);

                    /*
                    |--------------------------------------------------------------------------
                    | Riwayat
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanKtp
                        ->riwayat()
                        ->create([
                            'status' =>
                                $statusBaru,

                            'catatan' =>
                                $catatanBaru,

                            'changed_by' =>
                                $request->user()->id,
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Audit Log
                    |--------------------------------------------------------------------------
                    */

                    $auditLogService->record(
                        $request,
                        'update_status',
                        'pengajuan_ktp',
                        "Status pengajuan KTP #{$pengajuanKtp->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                        $pengajuanKtp,
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

                    return $pengajuanKtp->fresh([
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
                    'Status pengajuan KTP berhasil diperbarui.',

                'data' =>
                    new AdminPengajuanKtpResource(
                        $pengajuanKtp
                    ),
            ]);
        } catch (HttpException $e) {
            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], $e->getStatusCode());
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' =>
                    'Status pengajuan KTP gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Mendapatkan hari kerja berikutnya.
     *
     * Sabtu dan Minggu dilewati.
     */
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

    /**
     * Menghitung waktu expired.
     *
     * Expired pukul 13:00 pada hari ke-7
     * setelah tanggal kunjungan.
     */
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

    /**
     * Membuat nomor antrean unik.
     *
     * Format:
     * KTP-YYYYMMDD-XXXXX
     */
    private function generateNomorAntrian(
        Carbon $approvedAt
    ): string {
        do {
            $nomor =
                'KTP-' .
                $approvedAt->format('Ymd') .
                '-' .
                strtoupper(
                    Str::random(5)
                );
        } while (
            PengajuanKtp::query()
                ->where(
                    'no_antrian',
                    $nomor
                )
                ->exists()
        );

        return $nomor;
    }
}