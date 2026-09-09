<?php

namespace App\Http\Controllers;

use App\Models\KategoriUmkm;
use Illuminate\Http\JsonResponse;

class KategoriUmkmController extends Controller
{
    /**
     * Menampilkan kategori UMKM yang aktif.
     */
    public function index(): JsonResponse
    {
        $kategori = KategoriUmkm::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
            ]);

        return response()->json([
            'message' =>
                'Daftar kategori UMKM berhasil diambil.',

            'data' =>
                $kategori,
        ]);
    }
}