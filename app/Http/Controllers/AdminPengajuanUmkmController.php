<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengajuanUmkmStatusRequest;
use App\Http\Resources\AdminPengajuanUmkmResource;
use App\Models\PengajuanUmkm;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AdminPengajuanUmkmController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengajuan UMKM
     * untuk Admin / Super Admin.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanUmkm::query()
            ->with([
                'user',
                'kategori',
                'foto',
                'riwayat.changedBy' =>
                    fn ($query) =>
                        $query->latest(),
                'approvedBy',
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' =>
                'Daftar pengajuan UMKM berhasil diambil.',

            'data' =>
                AdminPengajuanUmkmResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /**
     * Menampilkan detail pengajuan UMKM
     * untuk Admin / Super Admin.
     */
    public function show(
        PengajuanUmkm $pengajuanUmkm
    ): JsonResponse {
        $pengajuanUmkm->load([
            'user',
            'kategori',
            'foto',
            'riwayat.changedBy' =>
                fn ($query) =>
                    $query->latest(),
            'approvedBy',
        ]);

        return response()->json([
            'message' =>
                'Detail pengajuan UMKM berhasil diambil.',

            'data' =>
                new AdminPengajuanUmkmResource(
                    $pengajuanUmkm
                ),
        ]);
    }

    /**
     * Memperbarui status pengajuan UMKM.
     *
     * Alur:
     *
     * menunggu_verifikasi
     *          ↓
     *       diproses
     *       ↙     ↘
     * disetujui   ditolak
     *
     * Status disetujui dan ditolak bersifat final.
     */
    public function updateStatus(
        UpdatePengajuanUmkmStatusRequest $request,
        PengajuanUmkm $pengajuanUmkm,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $updatedPengajuan = DB::transaction(
                function () use (
                    $request,
                    $pengajuanUmkm,
                    $auditLogService
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock Record
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanUmkm =
                        PengajuanUmkm::query()
                            ->whereKey(
                                $pengajuanUmkm->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | Validated Data
                    |--------------------------------------------------------------------------
                    */

                    $validated =
                        $request->validated();

                    $statusLama =
                        $pengajuanUmkm->status;

                    $statusBaru =
                        $validated['status'];

                    /*
                    |--------------------------------------------------------------------------
                    | Transition Check
                    |--------------------------------------------------------------------------
                    */

                    $transisiDiizinkan = [
                        'menunggu_verifikasi' => [
                            'diproses',
                            'disetujui',
                            'ditolak',
                        ],

                        'diproses' => [
                            'disetujui',
                            'ditolak',
                        ],

                        'disetujui' => [],

                        'ditolak' => [],
                    ];

                    $allowedTransitions =
                        $transisiDiizinkan[
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
                            "Status tidak dapat diubah dari {$statusLama} menjadi {$statusBaru}."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Nilai Lama
                    |--------------------------------------------------------------------------
                    */

                    $catatanLama =
                        $pengajuanUmkm->catatan_admin;

                    $approvedByLama =
                        $pengajuanUmkm->approved_by;

                    $approvedAtLama =
                        $pengajuanUmkm->approved_at;

                    $isActiveLama =
                        (bool) $pengajuanUmkm->is_active;

                    /*
                    |--------------------------------------------------------------------------
                    | Catatan Admin
                    |--------------------------------------------------------------------------
                    |
                    | Catatan hanya disimpan dari request saat status
                    | sedang diproses.
                    |
                    | Untuk ditolak:
                    | - wajib dari FormRequest
                    |
                    | Untuk diproses / disetujui:
                    | - boleh kosong
                    | - tidak mewarisi catatan lama
                    |
                    */

                    $catatanBaru =
                        $validated['catatan_admin']
                        ?? null;

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

                        'is_active' =>
                            false,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Jika Disetujui
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $statusBaru ===
                        'disetujui'
                    ) {
                        $data[
                            'approved_by'
                        ] =
                            $request
                                ->user()
                                ->id;

                        $data[
                            'approved_at'
                        ] =
                            now();

                        $data[
                            'is_active'
                        ] =
                            true;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Update Pengajuan
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanUmkm->update(
                        $data
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Riwayat Status
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanUmkm
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
                    */

                    $auditLogService->record(
                        $request,
                        'update_status',
                        'pengajuan_umkm',
                        "Status pengajuan UMKM #{$pengajuanUmkm->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                        $pengajuanUmkm,
                        [
                            'status' =>
                                $statusLama,

                            'catatan_admin' =>
                                $catatanLama,

                            'approved_by' =>
                                $approvedByLama,

                            'approved_at' =>
                                $this->normalizeDateTime(
                                    $approvedAtLama
                                ),

                            'is_active' =>
                                $isActiveLama,
                        ],
                        [
                            'status' =>
                                $statusBaru,

                            'catatan_admin' =>
                                $catatanBaru,

                            'approved_by' =>
                                $data[
                                    'approved_by'
                                ],

                            'approved_at' =>
                                $this->normalizeDateTime(
                                    $data[
                                        'approved_at'
                                    ]
                                ),

                            'is_active' =>
                                $data[
                                    'is_active'
                                ],
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Fresh Data
                    |--------------------------------------------------------------------------
                    */

                    return $pengajuanUmkm->fresh([
                        'user',
                        'kategori',
                        'foto',
                        'riwayat.changedBy' =>
                            fn ($query) =>
                                $query->latest(),
                        'approvedBy',
                    ]);
                }
            );

            return response()->json([
                'message' =>
                    'Status pengajuan UMKM berhasil diperbarui.',

                'data' =>
                    new AdminPengajuanUmkmResource(
                        $updatedPengajuan
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
                    'Status pengajuan UMKM gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Normalisasi datetime untuk audit log.
     */
    private function normalizeDateTime(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (
            $value instanceof \DateTimeInterface
        ) {
            return $value->format(
                'Y-m-d H:i:s'
            );
        }

        return (string) $value;
    }
}