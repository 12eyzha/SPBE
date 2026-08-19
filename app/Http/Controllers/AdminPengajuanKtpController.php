<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengajuanKtpStatusRequest;
use App\Http\Resources\AdminPengajuanKtpResource;
use App\Models\PengajuanKtp;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AdminPengajuanKtpController extends Controller
{
    /**
     * Menampilkan daftar seluruh pengajuan KTP
     * untuk Admin / Super Admin.
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
            'message' => 'Daftar pengajuan KTP berhasil diambil.',
            'data' => AdminPengajuanKtpResource::collection($pengajuan),
        ]);
    }

    /**
     * Menampilkan detail pengajuan KTP
     * untuk Admin / Super Admin.
     */
    public function show(PengajuanKtp $pengajuanKtp): JsonResponse
    {
        $pengajuanKtp->load([
            'user',
            'dokumen',
            'riwayat.changedBy',
            'approvedBy',
        ]);

        return response()->json([
            'message' => 'Detail pengajuan KTP berhasil diambil.',
            'data' => new AdminPengajuanKtpResource($pengajuanKtp),
        ]);
    }

    /**
     * Memperbarui status pengajuan KTP.
     */
    public function updateStatus(
        UpdatePengajuanKtpStatusRequest $request,
        PengajuanKtp $pengajuanKtp,
        AuditLogService $auditLogService
    ): JsonResponse {
        try {
            $pengajuanKtp = DB::transaction(function () use (
                $request,
                $pengajuanKtp,
                $auditLogService
            ) {
                $validated = $request->validated();

                $statusLama = $pengajuanKtp->status;
                $statusBaru = $validated['status'];

                $catatanLama = $pengajuanKtp->catatan_admin;
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

                    $data['no_antrian'] = $pengajuanKtp->no_antrian
                        ?: $this->generateNomorAntrian();
                }

                /*
                |--------------------------------------------------------------------------
                | Update Pengajuan
                |--------------------------------------------------------------------------
                */

                $pengajuanKtp->update($data);

                /*
                |--------------------------------------------------------------------------
                | Simpan Riwayat
                |--------------------------------------------------------------------------
                */

                $pengajuanKtp->riwayat()->create([
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
                    'pengajuan_ktp',
                    "Status pengajuan KTP #{$pengajuanKtp->id} diubah dari {$statusLama} menjadi {$statusBaru}.",
                    $pengajuanKtp,
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

                return $pengajuanKtp->fresh([
                    'user',
                    'dokumen',
                    'riwayat' => fn ($query) => $query->latest(),
                    'approvedBy',
                ]);
            });

            return response()->json([
                'message' => 'Status pengajuan KTP berhasil diperbarui.',
                'data' => new AdminPengajuanKtpResource($pengajuanKtp),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Status pengajuan KTP gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Membuat nomor antrean unik untuk pengajuan KTP.
     *
     * Format:
     * KTP-YYYYMMDD-XXXXX
     */
    private function generateNomorAntrian(): string
    {
        do {
            $nomor = 'KTP-' . now()->format('Ymd') . '-' . strtoupper(
                Str::random(5)
            );
        } while (
            PengajuanKtp::query()
                ->where('no_antrian', $nomor)
                ->exists()
        );

        return $nomor;
    }
}