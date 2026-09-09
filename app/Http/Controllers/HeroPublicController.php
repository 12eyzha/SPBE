<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;

class HeroPublicController extends Controller
{
    /**
     * Menampilkan seluruh hero aktif.
     *
     * Slide pertama adalah welcome.
     * Slide berikutnya dapat terhubung ke berita.
     */
    public function index(): JsonResponse
    {
        $heroSlides = HeroSlide::query()
            ->where('is_active', true)
            ->with([
                'berita:id,judul,slug',
            ])
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function (HeroSlide $heroSlide) {
                return [
                    'id' => $heroSlide->id,
                    'title' => $heroSlide->title,
                    'subtitle' => $heroSlide->subtitle,
                    'urutan' => $heroSlide->urutan,
                    'is_active' => $heroSlide->is_active,

                    // URL file public
                    'image_url' => url(
                        "/api/files/public/hero/{$heroSlide->id}"
                    ),

                    // Data berita kalau slide adalah berita
                    'berita' => $heroSlide->berita
                        ? [
                            'id' => $heroSlide->berita->id,
                            'judul' => $heroSlide->berita->judul,
                            'slug' => $heroSlide->berita->slug,
                        ]
                        : null,
                ];
            });

        return response()->json([
            'message' => 'Data hero berhasil diambil.',
            'data' => $heroSlides,
        ]);
    }

    /**
     * Detail hero aktif.
     */
    public function show(HeroSlide $heroSlide): JsonResponse
    {
        abort_unless(
            $heroSlide->is_active,
            404
        );

        $heroSlide->load([
            'berita:id,judul,slug',
        ]);

        return response()->json([
            'message' => 'Detail hero berhasil diambil.',
            'data' => [
                'id' => $heroSlide->id,
                'title' => $heroSlide->title,
                'subtitle' => $heroSlide->subtitle,
                'urutan' => $heroSlide->urutan,
                'is_active' => $heroSlide->is_active,

                'image_url' => url(
                    "/api/files/public/hero/{$heroSlide->id}"
                ),

                'berita' => $heroSlide->berita
                    ? [
                        'id' => $heroSlide->berita->id,
                        'judul' => $heroSlide->berita->judul,
                        'slug' => $heroSlide->berita->slug,
                    ]
                    : null,
            ],
        ]);
    }
}