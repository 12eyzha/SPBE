<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeritaRequest;
use App\Http\Requests\UpdateBeritaRequest;
use App\Http\Resources\BeritaResource;
use App\Models\Berita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AdminBeritaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | Menampilkan seluruh berita untuk Admin / Super Admin.
    |
    */

    public function index(): JsonResponse
    {
        $berita = Berita::query()
            ->with([
                'createdBy',
                'updatedBy',
            ])
            ->latest()
            ->paginate(20);

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
    | Menampilkan detail berita.
    |
    */

    public function show(
        Berita $berita
    ): JsonResponse {
        $berita->load([
            'createdBy',
            'updatedBy',
        ]);

        return response()->json([
            'message' =>
                'Detail berita berhasil diambil.',

            'data' =>
                new BeritaResource(
                    $berita
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    |
    | Membuat berita baru.
    |
    */

    public function store(
        StoreBeritaRequest $request
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | File yang berhasil disimpan
        |--------------------------------------------------------------------------
        |
        | Digunakan untuk cleanup jika transaksi database gagal.
        |
        */

        $storedThumbnailPath = null;

        try {
            $berita = DB::transaction(
                function () use (
                    $request,
                    &$storedThumbnailPath
                ) {
                    $validated =
                        $request->validated();

                    $status =
                        $validated['status'];

                    /*
                    |--------------------------------------------------------------------------
                    | Generate Slug
                    |--------------------------------------------------------------------------
                    */

                    $slug =
                        $this->generateUniqueSlug(
                            $validated['judul']
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Published At
                    |--------------------------------------------------------------------------
                    */

                    $publishedAt =
                        $status === 'published'
                            ? now()
                            : null;

                    /*
                    |--------------------------------------------------------------------------
                    | Thumbnail
                    |--------------------------------------------------------------------------
                    */

                    $thumbnailPath = null;

                    if (
                        $request->hasFile(
                            'thumbnail'
                        )
                    ) {
                        $thumbnailPath =
                            $request
                                ->file(
                                    'thumbnail'
                                )
                                ->store(
                                    'berita',
                                    'local'
                                );

                        $storedThumbnailPath =
                            $thumbnailPath;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Buat Berita
                    |--------------------------------------------------------------------------
                    */

                    $berita =
                        Berita::create([
                            'judul' =>
                                $validated[
                                    'judul'
                                ],

                            'slug' =>
                                $slug,

                            'isi' =>
                                $validated[
                                    'isi'
                                ],

                            'thumbnail' =>
                                $thumbnailPath,

                            'status' =>
                                $status,

                            'published_at' =>
                                $publishedAt,

                            'created_by' =>
                                $request
                                    ->user()
                                    ->id,

                            'updated_by' =>
                                $request
                                    ->user()
                                    ->id,
                        ]);

                    return $berita->load([
                        'createdBy',
                        'updatedBy',
                    ]);
                }
            );

            return response()->json([
                'message' =>
                    'Berita berhasil dibuat.',

                'data' =>
                    new BeritaResource(
                        $berita
                    ),
            ], 201);
        } catch (
            Throwable $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Thumbnail
            |--------------------------------------------------------------------------
            */

            if (
                $storedThumbnailPath
            ) {
                Storage::disk(
                    'local'
                )->delete(
                    $storedThumbnailPath
                );
            }

            report($e);

            return response()->json([
                'message' =>
                    'Berita gagal dibuat.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    |
    | Mengubah berita.
    |
    */

    public function update(
        UpdateBeritaRequest $request,
        Berita $berita
    ): JsonResponse {
        $oldThumbnailPath =
            $berita->thumbnail;

        $newThumbnailPath = null;

        try {
            $berita = DB::transaction(
                function () use (
                    $request,
                    $berita,
                    &$newThumbnailPath
                ) {
                    $validated =
                        $request->validated();

                    /*
                    |--------------------------------------------------------------------------
                    | Generate Slug Baru
                    |--------------------------------------------------------------------------
                    |
                    | Slug dibuat ulang berdasarkan judul terbaru.
                    |
                    */

                    $slug =
                        $this->generateUniqueSlug(
                            $validated['judul'],
                            $berita->id
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Status
                    |--------------------------------------------------------------------------
                    */

                    $status =
                        $validated['status'];

                    /*
                    |--------------------------------------------------------------------------
                    | Published At
                    |--------------------------------------------------------------------------
                    |
                    | Jika published:
                    |
                    | - berita sebelumnya draft -> now()
                    | - berita sebelumnya published -> pertahankan tanggal lama
                    |
                    | Jika draft:
                    |
                    | - null
                    |
                    */

                    if (
                        $status === 'published'
                    ) {
                        $publishedAt =
                            $berita->published_at ??
                            now();
                    } else {
                        $publishedAt = null;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Data Update
                    |--------------------------------------------------------------------------
                    */

                    $data = [
                        'judul' =>
                            $validated[
                                'judul'
                            ],

                        'slug' =>
                            $slug,

                        'isi' =>
                            $validated[
                                'isi'
                            ],

                        'status' =>
                            $status,

                        'published_at' =>
                            $publishedAt,

                        'updated_by' =>
                            $request
                                ->user()
                                ->id,
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Thumbnail Baru
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $request->hasFile(
                            'thumbnail'
                        )
                    ) {
                        $newThumbnailPath =
                            $request
                                ->file(
                                    'thumbnail'
                                )
                                ->store(
                                    'berita',
                                    'local'
                                );

                        $data[
                            'thumbnail'
                        ] =
                            $newThumbnailPath;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Update
                    |--------------------------------------------------------------------------
                    */

                    $berita->update(
                        $data
                    );

                    return $berita->fresh([
                        'createdBy',
                        'updatedBy',
                    ]);
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Hapus Thumbnail Lama
            |--------------------------------------------------------------------------
            |
            | Dilakukan setelah transaksi berhasil.
            |
            */

            if (
                $newThumbnailPath &&
                $oldThumbnailPath &&
                $oldThumbnailPath !==
                    $newThumbnailPath
            ) {
                Storage::disk(
                    'local'
                )->delete(
                    $oldThumbnailPath
                );
            }

            return response()->json([
                'message' =>
                    'Berita berhasil diperbarui.',

                'data' =>
                    new BeritaResource(
                        $berita
                    ),
            ]);
        } catch (
            Throwable $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Thumbnail Baru
            |--------------------------------------------------------------------------
            */

            if (
                $newThumbnailPath
            ) {
                Storage::disk(
                    'local'
                )->delete(
                    $newThumbnailPath
                );
            }

            report($e);

            return response()->json([
                'message' =>
                    'Berita gagal diperbarui.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLISH
    |--------------------------------------------------------------------------
    |
    | Helper khusus untuk publish berita.
    |
    */

    public function publish(
        Berita $berita
    ): JsonResponse {
        try {
            $berita = DB::transaction(
                function () use (
                    $berita
                ) {
                    $berita->update([
                        'status' =>
                            'published',

                        'published_at' =>
                            $berita
                                ->published_at ??
                            now(),

                        'updated_by' =>
                            auth()->id(),
                    ]);

                    return $berita->fresh([
                        'createdBy',
                        'updatedBy',
                    ]);
                }
            );

            return response()->json([
                'message' =>
                    'Berita berhasil dipublikasikan.',

                'data' =>
                    new BeritaResource(
                        $berita
                    ),
            ]);
        } catch (
            Throwable $e
        ) {
            report($e);

            return response()->json([
                'message' =>
                    'Berita gagal dipublikasikan.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UNPUBLISH
    |--------------------------------------------------------------------------
    |
    | Mengembalikan berita published menjadi draft.
    |
    */

    public function unpublish(
        Berita $berita
    ): JsonResponse {
        try {
            $berita = DB::transaction(
                function () use (
                    $berita
                ) {
                    $berita->update([
                        'status' =>
                            'draft',

                        'published_at' =>
                            null,

                        'updated_by' =>
                            auth()->id(),
                    ]);

                    return $berita->fresh([
                        'createdBy',
                        'updatedBy',
                    ]);
                }
            );

            return response()->json([
                'message' =>
                    'Berita berhasil dikembalikan menjadi draft.',

                'data' =>
                    new BeritaResource(
                        $berita
                    ),
            ]);
        } catch (
            Throwable $e
        ) {
            report($e);

            return response()->json([
                'message' =>
                    'Berita gagal dikembalikan menjadi draft.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    |
    | Menghapus berita dan thumbnail-nya.
    |
    */

    public function destroy(
        Berita $berita
    ): JsonResponse {
        $thumbnailPath =
            $berita->thumbnail;

        try {
            DB::transaction(
                function () use (
                    $berita
                ) {
                    $berita->delete();
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Hapus Thumbnail
            |--------------------------------------------------------------------------
            */

            if (
                $thumbnailPath
            ) {
                Storage::disk(
                    'local'
                )->delete(
                    $thumbnailPath
                );
            }

            return response()->json([
                'message' =>
                    'Berita berhasil dihapus.',
            ]);
        } catch (
            Throwable $e
        ) {
            report($e);

            return response()->json([
                'message' =>
                    'Berita gagal dihapus.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique Slug
    |--------------------------------------------------------------------------
    */

    private function generateUniqueSlug(
        string $judul,
        ?int $ignoreId = null
    ): string {
        $baseSlug =
            Str::slug(
                $judul
            );

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        */

        if (
            $baseSlug === ''
        ) {
            $baseSlug =
                'berita';
        }

        $slug =
            $baseSlug;

        $counter = 1;

        while (
            Berita::query()
                ->where(
                    'slug',
                    $slug
                )
                ->when(
                    $ignoreId !== null,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $ignoreId
                        )
                )
                ->exists()
        ) {
            $counter++;

            $slug =
                $baseSlug .
                '-' .
                $counter;
        }

        return $slug;
    }
}