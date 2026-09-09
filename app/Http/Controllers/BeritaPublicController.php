<?php

namespace App\Http\Controllers;

use App\Http\Resources\BeritaResource;
use App\Models\Berita;
use Illuminate\Http\JsonResponse;

class BeritaPublicController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | Menampilkan daftar berita yang sudah dipublikasikan.
    |
    */

    public function index(): JsonResponse
    {
        $berita = Berita::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->with([
                'createdBy',
            ])
            ->latest('published_at')
            ->paginate(12);

        return response()->json([
            'message' =>
                'Daftar berita berhasil diambil.',

            'data' =>
                BeritaResource::collection(
                    $berita
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    |
    | Menampilkan detail berita berdasarkan slug.
    |
    */

    public function show(
        string $slug
    ): JsonResponse {
        $berita = Berita::query()
            ->where(
                'slug',
                $slug
            )
            ->where(
                'status',
                'published'
            )
            ->whereNotNull(
                'published_at'
            )
            ->with([
                'createdBy',
            ])
            ->firstOrFail();

        return response()->json([
            'message' =>
                'Detail berita berhasil diambil.',

            'data' =>
                new BeritaResource(
                    $berita
                ),
        ]);
    }
}