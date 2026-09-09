<?php

namespace App\Http\Controllers;

use App\Http\Resources\PerangkatDesaResource;
use App\Models\PerangkatDesa;
use Illuminate\Http\JsonResponse;

class PerangkatDesaController extends Controller
{
    /**
     * Menampilkan perangkat desa yang aktif untuk publik.
     *
     * GET /api/perangkat-desa
     */
    public function index(): JsonResponse
    {
        $perangkat = PerangkatDesa::query()
            ->with('updatedBy:id,name')
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'urutan'
            )
            ->orderBy(
                'id'
            )
            ->get();

        return response()->json([
            'message' =>
                'Daftar perangkat desa berhasil diambil.',

            'data' =>
                PerangkatDesaResource::collection(
                    $perangkat
                ),
        ]);
    }
}