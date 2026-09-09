<?php

namespace App\Http\Controllers;

use App\Http\Resources\UmkmPublicResource;
use App\Models\PengajuanUmkm;
use Illuminate\Http\JsonResponse;

class UmkmPublicController extends Controller
{
    /**
     * Menampilkan daftar UMKM yang sudah disetujui
     * dan sedang aktif di publik.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanUmkm::query()
            ->where('status', 'disetujui')
            ->where('is_active', true)
            ->with([
                'kategori',
                'foto',
            ])
            ->latest()
            ->paginate(12);

        return response()->json([
            'message' =>
                'Daftar UMKM berhasil diambil.',

            'data' =>
                UmkmPublicResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /**
     * Menampilkan detail UMKM yang aktif di publik.
     */
    public function show(
        PengajuanUmkm $pengajuanUmkm
    ): JsonResponse {
        abort_unless(
            $pengajuanUmkm->status === 'disetujui' &&
            $pengajuanUmkm->is_active === true,
            404,
            'UMKM tidak ditemukan.'
        );

        $pengajuanUmkm->load([
            'kategori',
            'foto',
        ]);

        return response()->json([
            'message' =>
                'Detail UMKM berhasil diambil.',

            'data' =>
                new UmkmPublicResource(
                    $pengajuanUmkm
                ),
        ]);
    }
}