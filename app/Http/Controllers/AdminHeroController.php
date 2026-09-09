<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\HeroSlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminHeroController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | Menampilkan seluruh hero untuk Admin / Super Admin.
    |
    */

    /**
     * Menampilkan semua hero.
     */
    public function index(): JsonResponse
    {
        $heroSlides = HeroSlide::query()
            ->with([
                'berita:id,judul,slug',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(function (HeroSlide $heroSlide) {
                return [
                    'id' => $heroSlide->id,

                    'title' => $heroSlide->title,

                    'subtitle' => $heroSlide->subtitle,

                    'berita_id' => $heroSlide->berita_id,

                    'urutan' => $heroSlide->urutan,

                    'is_active' => $heroSlide->is_active,

                    /*
                    |--------------------------------------------------------------------------
                    | URL GAMBAR ADMIN
                    |--------------------------------------------------------------------------
                    */

                    'image_url' => url(
                        "/api/admin/files/hero/{$heroSlide->id}"
                    ),

                    /*
                    |--------------------------------------------------------------------------
                    | BERITA
                    |--------------------------------------------------------------------------
                    */

                    'berita' => $heroSlide->berita
                        ? [
                            'id' => $heroSlide->berita->id,
                            'judul' => $heroSlide->berita->judul,
                            'slug' => $heroSlide->berita->slug,
                        ]
                        : null,

                    /*
                    |--------------------------------------------------------------------------
                    | AUDIT
                    |--------------------------------------------------------------------------
                    */

                    'created_by' => $heroSlide->createdBy?->name,

                    'updated_by' => $heroSlide->updatedBy?->name,

                    'created_at' => $heroSlide->created_at,

                    'updated_at' => $heroSlide->updated_at,
                ];
            });

        return response()->json([
            'message' => 'Data hero berhasil diambil.',

            'data' => $heroSlides,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan detail hero.
     */
    public function show(
        HeroSlide $heroSlide
    ): JsonResponse {
        $heroSlide->load([
            'berita:id,judul,slug',
        ]);

        return response()->json([
            'message' => 'Detail hero berhasil diambil.',

            'data' => [
                'id' => $heroSlide->id,

                'title' => $heroSlide->title,

                'subtitle' => $heroSlide->subtitle,

                'berita_id' => $heroSlide->berita_id,

                'urutan' => $heroSlide->urutan,

                'is_active' => $heroSlide->is_active,

                'image_url' => url(
                    "/api/admin/files/hero/{$heroSlide->id}"
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

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    /**
     * Menambah hero baru.
     */
    public function store(
        Request $request
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'subtitle' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'berita_id' => [
                'nullable',
                'integer',
                'exists:berita,id',
            ],

            'urutan' => [
                'required',
                'integer',
                'min:1',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI URUTAN
        |--------------------------------------------------------------------------
        |
        | Urutan 1 = Welcome.
        |
        | Aturan:
        | - Tidak boleh memiliki berita.
        | - Selalu aktif.
        |
        */

        $urutan = (int) $validated['urutan'];

        if ($urutan === 1) {
            $validated['berita_id'] = null;

            $validated['is_active'] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | SLIDE 2 DAN SETERUSNYA
        |--------------------------------------------------------------------------
        |
        | Wajib memilih berita yang sudah published.
        |
        */

        if ($urutan >= 2) {
            if (
                empty(
                    $validated['berita_id']
                )
            ) {
                throw ValidationException::withMessages([
                    'berita_id' => [
                        'Slide berita wajib memilih berita.',
                    ],
                ]);
            }

            $berita = Berita::query()
                ->where(
                    'id',
                    $validated['berita_id']
                )
                ->where(
                    'status',
                    'published'
                )
                ->first();

            if (!$berita) {
                throw ValidationException::withMessages([
                    'berita_id' => [
                        'Berita yang dipilih harus sudah dipublikasikan.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CEK DUPLIKAT URUTAN
        |--------------------------------------------------------------------------
        */

        $urutanExists = HeroSlide::query()
            ->where(
                'urutan',
                $urutan
            )
            ->exists();

        if ($urutanExists) {
            throw ValidationException::withMessages([
                'urutan' => [
                    'Nomor urutan tersebut sudah digunakan.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN FILE
        |--------------------------------------------------------------------------
        */

        $filePath = $request
            ->file('image')
            ->store(
                'hero-slides',
                'local'
            );

        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATABASE
        |--------------------------------------------------------------------------
        */

        $heroSlide = HeroSlide::create([
            'image_path' => $filePath,

            'title' => $validated['title'],

            'subtitle' => $validated['subtitle'] ?? null,

            'berita_id' => $validated['berita_id'] ?? null,

            'urutan' => $urutan,

            'is_active' => $validated['is_active'] ?? true,

            'created_by' => Auth::id(),

            'updated_by' => Auth::id(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Hero berhasil ditambahkan.',

            'data' => $heroSlide,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    /**
     * Mengubah hero.
     */
    public function update(
        Request $request,
        HeroSlide $heroSlide
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'subtitle' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'berita_id' => [
                'nullable',
                'integer',
                'exists:berita,id',
            ],

            'urutan' => [
                'required',
                'integer',
                'min:1',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI URUTAN
        |--------------------------------------------------------------------------
        */

        $urutan = (int) $validated['urutan'];

        /*
        |--------------------------------------------------------------------------
        | SLIDE 1 = WELCOME
        |--------------------------------------------------------------------------
        |
        | Aturan:
        | - berita_id wajib null
        | - is_active wajib true
        |
        */

        if ($urutan === 1) {
            $validated['berita_id'] = null;

            $validated['is_active'] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | SLIDE 2+ = BERITA
        |--------------------------------------------------------------------------
        */

        if ($urutan >= 2) {
            if (
                empty(
                    $validated['berita_id']
                )
            ) {
                throw ValidationException::withMessages([
                    'berita_id' => [
                        'Slide berita wajib memilih berita.',
                    ],
                ]);
            }

            $berita = Berita::query()
                ->where(
                    'id',
                    $validated['berita_id']
                )
                ->where(
                    'status',
                    'published'
                )
                ->first();

            if (!$berita) {
                throw ValidationException::withMessages([
                    'berita_id' => [
                        'Berita yang dipilih harus sudah dipublikasikan.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CEK DUPLIKAT URUTAN
        |--------------------------------------------------------------------------
        */

        $urutanExists = HeroSlide::query()
            ->where(
                'urutan',
                $urutan
            )
            ->where(
                'id',
                '!=',
                $heroSlide->id
            )
            ->exists();

        if ($urutanExists) {
            throw ValidationException::withMessages([
                'urutan' => [
                    'Nomor urutan tersebut sudah digunakan.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FILE LAMA
        |--------------------------------------------------------------------------
        */

        $imagePath =
            $heroSlide->image_path;

        /*
        |--------------------------------------------------------------------------
        | JIKA ADA GAMBAR BARU
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile(
                'image'
            )
        ) {
            /*
            |--------------------------------------------------------------------------
            | HAPUS GAMBAR LAMA
            |--------------------------------------------------------------------------
            */

            if (
                filled(
                    $heroSlide->image_path
                ) &&
                Storage::disk('local')->exists(
                    $heroSlide->image_path
                )
            ) {
                Storage::disk('local')->delete(
                    $heroSlide->image_path
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SIMPAN GAMBAR BARU
            |--------------------------------------------------------------------------
            */

            $imagePath = $request
                ->file('image')
                ->store(
                    'hero-slides',
                    'local'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

        $heroSlide->update([
            'image_path' => $imagePath,

            'title' => $validated['title'],

            'subtitle' => $validated['subtitle'] ?? null,

            'berita_id' => $validated['berita_id'] ?? null,

            'urutan' => $urutan,

            'is_active' => $validated['is_active'] ?? false,

            'updated_by' => Auth::id(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Hero berhasil diperbarui.',

            'data' => $heroSlide,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    /**
     * Menghapus hero.
     */
    public function destroy(
        HeroSlide $heroSlide
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | WELCOME TIDAK BOLEH DIHAPUS
        |--------------------------------------------------------------------------
        |
        | Urutan 1 merupakan slide Welcome utama.
        |
        */

        if (
            (int) $heroSlide->urutan === 1
        ) {
            throw ValidationException::withMessages([
                'hero' => [
                    'Slide Welcome tidak boleh dihapus.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | HAPUS FILE
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $heroSlide->image_path
            ) &&
            Storage::disk('local')->exists(
                $heroSlide->image_path
            )
        ) {
            Storage::disk('local')->delete(
                $heroSlide->image_path
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HAPUS DATABASE
        |--------------------------------------------------------------------------
        */

        $heroSlide->delete();

        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Hero berhasil dihapus.',
        ]);
    }
}