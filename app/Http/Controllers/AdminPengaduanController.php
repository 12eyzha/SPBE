<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaduanResponRequest;
use App\Http\Requests\UpdatePengaduanStatusRequest;
use App\Http\Resources\AdminPengaduanResource;
use App\Models\Pengaduan;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AdminPengaduanController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengaduan
     * untuk Admin / Super Admin.
     */
    public function index(): JsonResponse
    {
        $pengaduan = Pengaduan::query()
            ->with([
                'user',

                'dokumen',

                'respon.user' =>
                    fn ($query) =>
                        $query->latest(),

                'riwayat.changedBy' =>
                    fn ($query) =>
                        $query->latest(),
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' =>
                'Daftar pengaduan berhasil diambil.',

            'data' =>
                AdminPengaduanResource::collection(
                    $pengaduan
                ),
        ]);
    }

    /**
     * Menampilkan detail pengaduan
     * untuk Admin / Super Admin.
     */
    public function show(
        Pengaduan $pengaduan
    ): JsonResponse {
        $pengaduan->load([
            'user',

            'dokumen',

            'respon.user' =>
                fn ($query) =>
                    $query->latest(),

            'riwayat.changedBy' =>
                fn ($query) =>
                    $query->latest(),
        ]);

        return response()->json([
            'message' =>
                'Detail pengaduan berhasil diambil.',

            'data' =>
                new AdminPengaduanResource(
                    $pengaduan
                ),
        ]);
    }

    /**
     * Menambahkan respons petugas ke pengaduan.
     *
     * Pengaduan yang sudah selesai tidak dapat
     * diberikan respons baru.
     *
     * Menambahkan respons tidak otomatis mengubah status.
     */
    public function storeRespon(
        StorePengaduanResponRequest $request,
        Pengaduan $pengaduan,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $respon =
                DB::transaction(
                    function () use (
                        $request,
                        $pengaduan,
                        $auditLogService
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock Pengaduan
                        |--------------------------------------------------------------------------
                        |
                        | Memastikan pengaduan tidak berubah secara bersamaan
                        | ketika respons sedang ditambahkan.
                        |
                        */

                        $pengaduan =
                            Pengaduan::query()
                                ->whereKey(
                                    $pengaduan->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        /*
                        |--------------------------------------------------------------------------
                        | Status Final
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $pengaduan->status ===
                            'selesai'
                        ) {
                            throw new HttpException(
                                422,
                                'Pengaduan yang sudah selesai tidak dapat diberikan respons baru.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Isi Respons
                        |--------------------------------------------------------------------------
                        */

                        $isiRespon =
                            $request->validated(
                                'respon'
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Respons
                        |--------------------------------------------------------------------------
                        |
                        | user_id menyimpan Admin / Super Admin
                        | yang memberikan respons.
                        |
                        */

                        $respon =
                            $pengaduan
                                ->respon()
                                ->create([
                                    'user_id' =>
                                        $request
                                            ->user()
                                            ->id,

                                    'respon' =>
                                        $isiRespon,
                                ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Audit Log
                        |--------------------------------------------------------------------------
                        */

                        $auditLogService->record(
                            $request,
                            'store_response',
                            'pengaduan',
                            "Respons petugas ditambahkan pada pengaduan #{$pengaduan->id}.",
                            $pengaduan,
                            null,
                            [
                                'respon_id' =>
                                    $respon->id,

                                'respon' =>
                                    $isiRespon,
                            ]
                        );

                        return $respon;
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Response Berhasil
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'message' =>
                    'Respons petugas berhasil ditambahkan.',

                'data' => [
                    'id' =>
                        $respon->id,

                    'user_id' =>
                        $respon->user_id,

                    'respon' =>
                        $respon->respon,

                    'created_at' =>
                        $respon
                            ->created_at
                            ?->toISOString(),
                ],
            ], 201);
        } catch (
            HttpException $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Validation / Business Rule Error
            |--------------------------------------------------------------------------
            */

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
                    'Respons petugas gagal ditambahkan.',
            ], 500);
        }
    }

    /**
     * Memperbarui status pengaduan.
     *
     * Alur:
     *
     * terkirim
     *     ↓
     * diteruskan
     *     ↓
     * selesai
     *
     * Setiap perubahan status dicatat
     * ke pengaduan_riwayat.
     */
    public function updateStatus(
        UpdatePengaduanStatusRequest $request,
        Pengaduan $pengaduan,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengaduan =
                DB::transaction(
                    function () use (
                        $request,
                        $pengaduan,
                        $auditLogService
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock Pengaduan
                        |--------------------------------------------------------------------------
                        |
                        | Memastikan dua Admin / Super Admin tidak
                        | memproses pengaduan yang sama secara bersamaan.
                        |
                        */

                        $pengaduan =
                            Pengaduan::query()
                                ->whereKey(
                                    $pengaduan->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        /*
                        |--------------------------------------------------------------------------
                        | Status
                        |--------------------------------------------------------------------------
                        */

                        $statusLama =
                            $pengaduan->status;

                        $statusBaru =
                            $request->validated(
                                'status'
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Tidak Ada Perubahan
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $statusLama ===
                            $statusBaru
                        ) {
                            return $pengaduan->fresh([
                                'user',

                                'dokumen',

                                'respon.user' =>
                                    fn ($query) =>
                                        $query->latest(),

                                'riwayat.changedBy' =>
                                    fn ($query) =>
                                        $query->latest(),
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Update Status
                        |--------------------------------------------------------------------------
                        */

                        $pengaduan->update([
                            'status' =>
                                $statusBaru,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Riwayat
                        |--------------------------------------------------------------------------
                        |
                        | Setiap perubahan status menyimpan:
                        | - status
                        | - catatan
                        | - user yang mengubah
                        |
                        */

                        $pengaduan
                            ->riwayat()
                            ->create([
                                'status' =>
                                    $statusBaru,

                                'catatan' =>
                                    $this->getStatusHistoryNote(
                                        $statusBaru
                                    ),

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
                            'pengaduan',
                            "Status pengaduan #{$pengaduan->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                            $pengaduan,
                            [
                                'status' =>
                                    $statusLama,
                            ],
                            [
                                'status' =>
                                    $statusBaru,
                            ]
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Fresh Response
                        |--------------------------------------------------------------------------
                        */

                        return $pengaduan->fresh([
                            'user',

                            'dokumen',

                            'respon.user' =>
                                fn ($query) =>
                                    $query->latest(),

                            'riwayat.changedBy' =>
                                fn ($query) =>
                                    $query->latest(),
                        ]);
                    }
                );

            return response()->json([
                'message' =>
                    'Status pengaduan berhasil diperbarui.',

                'data' =>
                    new AdminPengaduanResource(
                        $pengaduan
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
                    'Status pengaduan gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Membuat catatan standar untuk riwayat status.
     */
    private function getStatusHistoryNote(
        string $status
    ): string {
        return match ($status) {
            'terkirim' =>
                'Pengaduan berhasil dikirim.',

            'diteruskan' =>
                'Pengaduan telah diteruskan kepada petugas terkait.',

            'selesai' =>
                'Pengaduan telah selesai ditangani.',

            default =>
                'Status pengaduan diperbarui.',
        };
    }
}