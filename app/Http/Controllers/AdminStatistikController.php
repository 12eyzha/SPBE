<?php

namespace App\Http\Controllers;

use App\Models\StatistikDesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStatistikController extends Controller
{
    /**
     * Menampilkan statistik desa berdasarkan tahun.
     *
     * GET /api/admin/statistik?tahun=2026
     */
    public function index(
        Request $request
    ): JsonResponse {
        $tahun = (int) (
            $request->input(
                'tahun',
                now()->year
            )
        );

        $statistik =
            StatistikDesa::with(
                'updatedBy:id,name'
            )
                ->where(
                    'tahun',
                    $tahun
                )
                ->first();

        return response()->json([
            'message' =>
                $statistik
                    ? 'Data statistik berhasil diambil.'
                    : 'Data statistik belum tersedia.',

            'data' =>
                $statistik,
        ]);
    }

    /**
     * Menyimpan atau mengganti statistik desa.
     *
     * POST /api/admin/statistik
     *
     * Perilaku:
     *
     * Tahun belum ada:
     *     CREATE
     *
     * Tahun sudah ada:
     *     UPDATE
     *
     * Jadi tidak ada data statistik yang
     * terus bertambah pada tahun yang sama.
     */
    public function store(
        Request $request
    ): JsonResponse {
        $validated =
            $request->validate([
                /*
                |--------------------------------------------------------------------------
                | Tahun
                |--------------------------------------------------------------------------
                */

                'tahun' => [
                    'required',
                    'integer',
                    'min:2000',
                    'max:2100',
                ],

                /*
                |--------------------------------------------------------------------------
                | Kependudukan
                |--------------------------------------------------------------------------
                */

                'total_penduduk' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'total_kk' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'total_laki_laki' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'total_perempuan' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                /*
                |--------------------------------------------------------------------------
                | Bantuan Sosial
                |--------------------------------------------------------------------------
                */

                'total_pkh' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'total_blt_dd' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'total_bpnt' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                /*
                |--------------------------------------------------------------------------
                | Kelompok Usia
                |--------------------------------------------------------------------------
                |
                | SEKARANG NILAINYA ADALAH JUMLAH ORANG,
                | BUKAN PERSENTASE.
                |
                */

                'usia_0_14' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'usia_15_64' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'usia_65_plus' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                /*
                |--------------------------------------------------------------------------
                | IDM
                |--------------------------------------------------------------------------
                */

                'idm_nilai' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'idm_status' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Validasi Jenis Kelamin
        |--------------------------------------------------------------------------
        |
        | Laki-laki + perempuan harus sama dengan total penduduk.
        |
        */

        $totalGender =
            $validated['total_laki_laki'] +
            $validated['total_perempuan'];

        if (
            $totalGender !==
            $validated['total_penduduk']
        ) {
            return response()->json([
                'message' =>
                    'Total laki-laki dan perempuan harus sama dengan total penduduk.',

                'errors' => [
                    'total_laki_laki' => [
                        'Laki-laki + perempuan harus sama dengan total penduduk.',
                    ],

                    'total_perempuan' => [
                        'Laki-laki + perempuan harus sama dengan total penduduk.',
                    ],
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Kelompok Usia
        |--------------------------------------------------------------------------
        |
        | Ketiga field usia adalah JUMLAH ORANG.
        |
        | Contoh:
        |
        | total penduduk = 1000
        |
        | usia 0-14    = 200
        | usia 15-64   = 600
        | usia 65+     = 200
        |
        | total = 1000
        |
        */

        $totalUsia =
            $validated['usia_0_14'] +
            $validated['usia_15_64'] +
            $validated['usia_65_plus'];

        if (
            $totalUsia !==
            $validated['total_penduduk']
        ) {
            return response()->json([
                'message' =>
                    'Jumlah seluruh kelompok usia harus sama dengan total penduduk.',

                'errors' => [
                    'usia_0_14' => [
                        'Jumlah kelompok usia harus sama dengan total penduduk.',
                    ],

                    'usia_15_64' => [
                        'Jumlah kelompok usia harus sama dengan total penduduk.',
                    ],

                    'usia_65_plus' => [
                        'Jumlah kelompok usia harus sama dengan total penduduk.',
                    ],
                ],
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE OR CREATE
        |--------------------------------------------------------------------------
        |
        | Ini yang membuat data TIDAK grow.
        |
        | Contoh:
        |
        | 2026 sudah ada
        | -> update record 2026
        |
        | 2027 belum ada
        | -> buat record 2027
        |
        */

        $statistik =
            StatistikDesa::updateOrCreate(
                [
                    'tahun' =>
                        $validated['tahun'],
                ],
                [
                    'total_penduduk' =>
                        $validated['total_penduduk'],

                    'total_kk' =>
                        $validated['total_kk'],

                    'total_laki_laki' =>
                        $validated['total_laki_laki'],

                    'total_perempuan' =>
                        $validated['total_perempuan'],

                    'total_pkh' =>
                        $validated['total_pkh'],

                    'total_blt_dd' =>
                        $validated['total_blt_dd'],

                    'total_bpnt' =>
                        $validated['total_bpnt'],

                    /*
                    |--------------------------------------------------------------------------
                    | Jumlah Penduduk Berdasarkan Usia
                    |--------------------------------------------------------------------------
                    */

                    'usia_0_14' =>
                        $validated['usia_0_14'],

                    'usia_15_64' =>
                        $validated['usia_15_64'],

                    'usia_65_plus' =>
                        $validated['usia_65_plus'],

                    /*
                    |--------------------------------------------------------------------------
                    | IDM
                    |--------------------------------------------------------------------------
                    */

                    'idm_nilai' =>
                        $validated['idm_nilai'] ??
                        null,

                    'idm_status' =>
                        $validated['idm_status'] ??
                        null,

                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    'updated_by' =>
                        auth()->id(),
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Load User Terakhir Mengubah
        |--------------------------------------------------------------------------
        */

        $statistik->load(
            'updatedBy:id,name'
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' =>
                'Statistik desa berhasil disimpan.',

            'data' =>
                $statistik,
        ]);
    }
}