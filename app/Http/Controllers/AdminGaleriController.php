<?php

namespace App\Http\Controllers;

use App\Http\Resources\GaleriResource;
use App\Models\Galeri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminGaleriController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | GET /api/admin/galeri
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
    | STORE
    |--------------------------------------------------------------------------
    |
    | POST /api/admin/galeri
    |
    | Bisa menerima banyak file:
    |
    | foto[]
    |
    */

    public function store(
        Request $request
    ): JsonResponse {
        $request->validate([
            'foto' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'foto.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $files =
            $request->file('foto');

        $createdItems = [];

        DB::beginTransaction();

        try {
            foreach (
                $files as $file
            ) {
                if (
                    ! $file instanceof UploadedFile
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | SIMPAN FILE
                |--------------------------------------------------------------------------
                */

                $filePath =
                    $file->store(
                        'galeri',
                        'local'
                    );

                /*
                |--------------------------------------------------------------------------
                | CREATE DATABASE
                |--------------------------------------------------------------------------
                */

                $galeri =
                    Galeri::create([
                        'file_path' =>
                            $filePath,

                        'created_by' =>
                            auth()->id(),

                        'updated_by' =>
                            auth()->id(),
                    ]);

                $galeri->load([
                    'createdBy:id,name',
                    'updatedBy:id,name',
                ]);

                $createdItems[] =
                    $galeri;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            /*
            |--------------------------------------------------------------------------
            | CLEANUP FILE
            |--------------------------------------------------------------------------
            */

            foreach (
                $createdItems as $item
            ) {
                if (
                    filled(
                        $item->file_path
                    )
                ) {
                    Storage::disk('local')
                        ->delete(
                            $item->file_path
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | LOG
            |--------------------------------------------------------------------------
            */

            report($e);

            return response()->json([
                'message' =>
                    'Gagal mengunggah gambar galeri.',
            ], 500);
        }

        return response()->json([
            'message' =>
                count($createdItems) .
                ' gambar galeri berhasil diunggah.',

            'data' =>
                GaleriResource::collection(
                    collect(
                        $createdItems
                    )
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    |
    | GET /api/admin/galeri/{galeri}
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

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    |
    | PUT/PATCH sebenarnya bisa digunakan,
    | tetapi untuk file lebih aman frontend memakai POST.
    |
    | POST /api/admin/galeri/{galeri}
    |
    | Upload foto baru = opsional.
    | Kalau tidak ada foto baru, foto lama tetap digunakan.
    |
    */

    public function update(
        Request $request,
        Galeri $galeri
    ): JsonResponse {
        $request->validate([
            'foto' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | FOTO BARU
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile('foto')
        ) {
            $oldPath =
                $galeri->file_path;

            /*
            |--------------------------------------------------------------
            | SIMPAN FOTO BARU
            |--------------------------------------------------------------
            */

            $newPath =
                $request
                    ->file('foto')
                    ->store(
                        'galeri',
                        'local'
                    );

            /*
            |--------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------
            */

            $galeri->file_path =
                $newPath;

            $galeri->updated_by =
                auth()->id();

            $galeri->save();

            /*
            |--------------------------------------------------------------
            | HAPUS FOTO LAMA
            |--------------------------------------------------------------
            */

            if (
                filled($oldPath)
            ) {
                Storage::disk('local')
                    ->delete(
                        $oldPath
                    );
            }
        } else {
            /*
            |--------------------------------------------------------------------------
            | HANYA UPDATE AUDIT
            |--------------------------------------------------------------------------
            */

            $galeri->updated_by =
                auth()->id();

            $galeri->save();
        }

        $galeri->load([
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'message' =>
                'Gambar galeri berhasil diperbarui.',

            'data' =>
                new GaleriResource(
                    $galeri
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    |
    | DELETE /api/admin/galeri/{galeri}
    |
    */

    public function destroy(
        Galeri $galeri
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | HAPUS FILE
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $galeri->file_path
            )
        ) {
            Storage::disk('local')
                ->delete(
                    $galeri->file_path
                );
        }

        /*
        |--------------------------------------------------------------------------
        | HAPUS DATABASE
        |--------------------------------------------------------------------------
        */

        $galeri->delete();

        return response()->json([
            'message' =>
                'Gambar galeri berhasil dihapus.',

            'data' =>
                null,
        ]);
    }
}