<?php

namespace App\Http\Controllers;

use App\Http\Resources\GaleriResource;
use App\Models\Galeri;
use Illuminate\Http\JsonResponse;

class GaleriPublicController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX PUBLIC
    |--------------------------------------------------------------------------
    |
    | GET /api/galeri
    |
    */

    public function index(): JsonResponse
    {
        $galeri = Galeri::with([
            'createdBy:id,name',
            'updatedBy:id,name',
        ])
            ->latest('id')
            ->get();

        return response()->json([
            'message' =>
                'Daftar galeri berhasil diambil.',

            'data' =>
                GaleriResource::collection(
                    $galeri
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW PUBLIC
    |--------------------------------------------------------------------------
    |
    | GET /api/galeri/{galeri}
    |
    */

    public function show(
        Galeri $galeri
    ): JsonResponse {
        $galeri->load([
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'message' =>
                'Detail galeri berhasil diambil.',

            'data' =>
                new GaleriResource(
                    $galeri
                ),
        ]);
    }
}