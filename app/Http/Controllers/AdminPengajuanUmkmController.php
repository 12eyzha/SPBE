<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengajuanUmkmStatusRequest;
use App\Http\Resources\AdminPengajuanUmkmResource;
use App\Models\PengajuanUmkm;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
                'riwayat' => fn ($query) => $query->latest(),
                'approvedBy',
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Daftar pengajuan UMKM berhasil diambil.',
            'data' => AdminPengajuanUmkmResource::collection($pengajuan),
        ]);
    }

    /**
     * Menampilkan detail pengajuan UMKM
     * untuk Admin / Super Admin.
     */
    public function show(PengajuanUmkm $pengajuanUmkm): JsonResponse
    {
        $pengajuanUmkm->load([
            'user',
            'kategori',
            'foto',
            'riwayat.changedBy',
            'approvedBy',
        ]);

        return response()->json([
            'message' => 'Detail pengajuan UMKM berhasil diambil.',
            'data' => new AdminPengajuanUmkmResource($pengajuanUmkm),
        ]);
    }

    /**
     * Memperbarui status pengajuan UMKM.
     *
     * Alur:
     * menunggu_verifikasi → diproses → disetujui / ditolak
     */
    public function updateStatus(
        UpdatePengajuanUmkmStatusRequest $request,
        PengajuanUmkm $pengajuanUmkm,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengajuanUmkm = DB::transaction(function () use (
                $request,
                $pengajuanUmkm,
                $auditLogService
            ) {
                $validated = $request->validated();

                $statusLama = $pengajuanUmkm->status;
                $statusBaru = $validated['status'];

                $catatanLama = $pengajuanUmkm->catatan_admin;
                $catatanBaru = $validated['catatan_admin'] ?? null;

                $isActiveLama = $pengajuanUmkm->is_active;

                $data = [
                    'status' => $statusBaru,
                    'catatan_admin' => $catatanBaru,
                    'approved_by' => null,
                    'approved_at' => null,
                    'is_active' => false,
                ];

                /*
                |--------------------------------------------------------------------------
                | Pengajuan Disetujui
                |--------------------------------------------------------------------------
                */

                if ($statusBaru === 'disetujui') {
                    $data['approved_by'] = $request->user()->id;
                    $data['approved_at'] = now();
                    $data['is_active'] = true;
                }

                /*
                |--------------------------------------------------------------------------
                | Update Pengajuan
                |--------------------------------------------------------------------------
                */

                $pengajuanUmkm->update($data);

                /*
                |--------------------------------------------------------------------------
                | Simpan Riwayat
                |--------------------------------------------------------------------------
                */

                $pengajuanUmkm->riwayat()->create([
                    'status' => $statusBaru,
                    'catatan' => $catatanBaru,
                    'changed_by' => $request->user()->id,
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
                        'status' => $statusLama,
                        'catatan_admin' => $catatanLama,
                        'is_active' => $isActiveLama,
                    ],
                    [
                        'status' => $statusBaru,
                        'catatan_admin' => $catatanBaru,
                        'approved_by' => $data['approved_by'],
                        'approved_at' => $data['approved_at'],
                        'is_active' => $data['is_active'],
                    ]
                );

                return $pengajuanUmkm->fresh([
                    'user',
                    'kategori',
                    'foto',
                    'riwayat' => fn ($query) => $query->latest(),
                    'approvedBy',
                ]);
            });

            return response()->json([
                'message' => 'Status pengajuan UMKM berhasil diperbarui.',
                'data' => new AdminPengajuanUmkmResource($pengajuanUmkm),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Status pengajuan UMKM gagal diperbarui.',
            ], 500);
        }
    }
}