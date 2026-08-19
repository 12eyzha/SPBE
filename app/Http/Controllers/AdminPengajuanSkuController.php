<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengajuanSkuStatusRequest;
use App\Http\Resources\AdminPengajuanSkuResource;
use App\Models\PengajuanSku;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AdminPengajuanSkuController extends Controller
{
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
                'riwayat' => fn ($query) => $query->latest(),
                'approvedBy',
            ])
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Daftar pengajuan SKU berhasil diambil.',
            'data' => AdminPengajuanSkuResource::collection($pengajuan),
        ]);
    }

    /**
     * Menampilkan detail pengajuan SKU
     * untuk Admin / Super Admin.
     */
    public function show(PengajuanSku $pengajuanSku): JsonResponse
    {
        $pengajuanSku->load([
            'user',
            'dokumen',
            'riwayat.changedBy',
            'approvedBy',
        ]);

        return response()->json([
            'message' => 'Detail pengajuan SKU berhasil diambil.',
            'data' => new AdminPengajuanSkuResource($pengajuanSku),
        ]);
    }

    /**
     * Memperbarui status pengajuan SKU.
     */
    public function updateStatus(
        UpdatePengajuanSkuStatusRequest $request,
        PengajuanSku $pengajuanSku,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengajuanSku = DB::transaction(function () use (
                $request,
                $pengajuanSku,
                $auditLogService
            ) {
                $validated = $request->validated();

                $statusLama = $pengajuanSku->status;
                $statusBaru = $validated['status'];

                $catatanLama = $pengajuanSku->catatan_admin;
                $catatanBaru = $validated['catatan_admin'] ?? null;

                $data = [
                    'status' => $statusBaru,
                    'catatan_admin' => $catatanBaru,
                    'approved_by' => null,
                    'approved_at' => null,
                    'no_antrian' => null,
                ];

                /*
                |--------------------------------------------------------------------------
                | Pengajuan Disetujui
                |--------------------------------------------------------------------------
                */

                if ($statusBaru === 'disetujui') {
                    $data['approved_by'] = $request->user()->id;
                    $data['approved_at'] = now();

                    $data['no_antrian'] = $pengajuanSku->no_antrian
                        ?: $this->generateNomorAntrian();
                }

                /*
                |--------------------------------------------------------------------------
                | Update Pengajuan
                |--------------------------------------------------------------------------
                */

                $pengajuanSku->update($data);

                /*
                |--------------------------------------------------------------------------
                | Simpan Riwayat
                |--------------------------------------------------------------------------
                */

                $pengajuanSku->riwayat()->create([
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
                    'pengajuan_sku',
                    "Status pengajuan SKU #{$pengajuanSku->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                    $pengajuanSku,
                    [
                        'status' => $statusLama,
                        'catatan_admin' => $catatanLama,
                    ],
                    [
                        'status' => $statusBaru,
                        'catatan_admin' => $catatanBaru,
                        'approved_by' => $data['approved_by'],
                        'approved_at' => $data['approved_at'],
                        'no_antrian' => $data['no_antrian'],
                    ]
                );

                return $pengajuanSku->fresh([
                    'user',
                    'dokumen',
                    'riwayat' => fn ($query) => $query->latest(),
                    'approvedBy',
                ]);
            });

            return response()->json([
                'message' => 'Status pengajuan SKU berhasil diperbarui.',
                'data' => new AdminPengajuanSkuResource($pengajuanSku),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Status pengajuan SKU gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Membuat nomor antrean unik untuk pengajuan SKU.
     *
     * Format:
     * SKU-YYYYMMDD-XXXXX
     */
    private function generateNomorAntrian(): string
    {
        do {
            $nomor = 'SKU-' . now()->format('Ymd') . '-' . strtoupper(
                Str::random(5)
            );
        } while (
            PengajuanSku::query()
                ->where('no_antrian', $nomor)
                ->exists()
        );

        return $nomor;
    }
}