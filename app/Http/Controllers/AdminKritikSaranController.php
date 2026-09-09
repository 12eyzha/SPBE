<?php

namespace App\Http\Controllers;

use App\Models\KritikSaran;
use Illuminate\Http\JsonResponse;

class AdminKritikSaranController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | Menampilkan seluruh kritik dan saran dari masyarakat.
    |
    | GET /api/admin/kritik-saran
    |
    */

    public function index(): JsonResponse
    {
        $data = KritikSaran::query()
            ->latest('id')
            ->get();

        return response()->json([
            'message' =>
                'Daftar kritik dan saran berhasil diambil.',

            'data' =>
                $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    |
    | Menampilkan satu kritik dan saran.
    |
    | GET /api/admin/kritik-saran/{kritikSaran}
    |
    */

    public function show(
        KritikSaran $kritikSaran
    ): JsonResponse {
        return response()->json([
            'message' =>
                'Detail kritik dan saran berhasil diambil.',

            'data' =>
                $kritikSaran,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    |
    | Menghapus kritik dan saran.
    |
    | DELETE /api/admin/kritik-saran/{kritikSaran}
    |
    */

    public function destroy(
        KritikSaran $kritikSaran
    ): JsonResponse {
        $kritikSaran->delete();

        return response()->json([
            'message' =>
                'Kritik dan saran berhasil dihapus.',

            'data' =>
                null,
        ]);
    }
}