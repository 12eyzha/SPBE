<?php

namespace App\Http\Controllers;

use App\Models\StatistikDesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatistikDesaController extends Controller
{
    /**
     * Menampilkan statistik desa untuk publik.
     *
     * GET /api/statistik?tahun=2026
     */
    public function show(
        Request $request
    ): JsonResponse {
        $tahun = (int) (
            $request->input(
                'tahun',
                now()->year
            )
        );

        $statistik =
            StatistikDesa::query()
                ->where(
                    'tahun',
                    $tahun
                )
                ->first();

        if (
            !$statistik
        ) {
            return response()->json([
                'message' =>
                    'Data statistik tahun tersebut belum tersedia.',

                'data' =>
                    null,
            ], 404);
        }

        return response()->json([
            'message' =>
                'Data statistik berhasil diambil.',

            'data' => [
                'tahun' =>
                    $statistik->tahun,

                'total_penduduk' =>
                    $statistik->total_penduduk,

                'total_kk' =>
                    $statistik->total_kk,

                'total_laki_laki' =>
                    $statistik->total_laki_laki,

                'total_perempuan' =>
                    $statistik->total_perempuan,

                'total_pkh' =>
                    $statistik->total_pkh,

                'total_blt_dd' =>
                    $statistik->total_blt_dd,

                'total_bpnt' =>
                    $statistik->total_bpnt,

                /*
                |--------------------------------------------------------------------------
                | KELOMPOK USIA
                |--------------------------------------------------------------------------
                |
                | Database menyimpan jumlah orang.
                |
                */

                'usia_0_14' =>
                    $statistik->usia_0_14,

                'usia_15_64' =>
                    $statistik->usia_15_64,

                'usia_65_plus' =>
                    $statistik->usia_65_plus,

                /*
                |--------------------------------------------------------------------------
                | IDM
                |--------------------------------------------------------------------------
                */

                'idm_nilai' =>
                    $statistik->idm_nilai,

                'idm_status' =>
                    $statistik->idm_status,

                'updated_at' =>
                    $statistik->updated_at?->toISOString(),
            ],
        ]);
    }
}