<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaduanResponRequest;
use App\Http\Requests\UpdatePengaduanStatusRequest;
use App\Http\Resources\AdminPengaduanResource;
use App\Models\Pengaduan;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
                'dokumen',
                'respon' => fn ($query) => $query->latest(),
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Daftar pengaduan berhasil diambil.',
            'data' => AdminPengaduanResource::collection($pengaduan),
        ]);
    }

    /**
     * Menampilkan detail pengaduan
     * untuk Admin / Super Admin.
     */
    public function show(Pengaduan $pengaduan): JsonResponse
    {
        $pengaduan->load([
            'dokumen',
            'respon' => fn ($query) => $query->latest(),
        ]);

        return response()->json([
            'message' => 'Detail pengaduan berhasil diambil.',
            'data' => new AdminPengaduanResource($pengaduan),
        ]);
    }

    /**
     * Menambahkan respons petugas ke pengaduan.
     */
    public function storeRespon(
        StorePengaduanResponRequest $request,
        Pengaduan $pengaduan,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $respon = DB::transaction(function () use (
                $request,
                $pengaduan,
                $auditLogService
            ) {
                $isiRespon = $request->validated('respon');

                $respon = $pengaduan->respon()->create([
                    'respon' => $isiRespon,
                ]);

                $auditLogService->record(
                    $request,
                    'store_response',
                    'pengaduan',
                    "Respons petugas ditambahkan pada pengaduan #{$pengaduan->id}.",
                    $pengaduan,
                    null,
                    [
                        'respon_id' => $respon->id,
                        'respon' => $isiRespon,
                    ]
                );

                return $respon;
            });

            return response()->json([
                'message' => 'Respons petugas berhasil ditambahkan.',
                'data' => [
                    'id' => $respon->id,
                    'respon' => $respon->respon,
                    'created_at' => $respon->created_at?->toISOString(),
                ],
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Respons petugas gagal ditambahkan.',
            ], 500);
        }
    }

    /**
     * Memperbarui status pengaduan.
     *
     * Alur:
     * terkirim → diteruskan → selesai
     */
    public function updateStatus(
        UpdatePengaduanStatusRequest $request,
        Pengaduan $pengaduan,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengaduan = DB::transaction(function () use (
                $request,
                $pengaduan,
                $auditLogService
            ) {
                $statusLama = $pengaduan->status;
                $statusBaru = $request->validated('status');

                $pengaduan->update([
                    'status' => $statusBaru,
                ]);

                $auditLogService->record(
                    $request,
                    'update_status',
                    'pengaduan',
                    "Status pengaduan #{$pengaduan->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                    $pengaduan,
                    [
                        'status' => $statusLama,
                    ],
                    [
                        'status' => $statusBaru,
                    ]
                );

                return $pengaduan->fresh([
                    'dokumen',
                    'respon' => fn ($query) => $query->latest(),
                ]);
            });

            return response()->json([
                'message' => 'Status pengaduan berhasil diperbarui.',
                'data' => new AdminPengaduanResource($pengaduan),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Status pengaduan gagal diperbarui.',
            ], 500);
        }
    }
}