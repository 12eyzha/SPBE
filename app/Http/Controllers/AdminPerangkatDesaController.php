<?php

namespace App\Http\Controllers;

use App\Http\Resources\PerangkatDesaResource;
use App\Models\PerangkatDesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPerangkatDesaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    |
    | GET /api/admin/perangkat-desa
    |
    */

    public function index(): JsonResponse
    {
        $perangkat =
            PerangkatDesa::query()
                ->with(
                    'updatedBy:id,name'
                )
                ->orderBy('urutan')
                ->orderBy('id')
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

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    |
    | POST /api/admin/perangkat-desa
    |
    */

    public function store(
        Request $request
    ): JsonResponse {
        $validated =
            $request->validate([
                'nama' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'jabatan' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'foto' => [
                    'required',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120',
                ],

                'urutan' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN FOTO
        |--------------------------------------------------------------------------
        */

        $fotoPath =
            $request
                ->file('foto')
                ->store(
                    'perangkat-desa',
                    'local'
                );

        /*
        |--------------------------------------------------------------------------
        | CREATE
        |--------------------------------------------------------------------------
        */

        $perangkat =
            PerangkatDesa::create([
                'nama' =>
                    $validated['nama'],

                'jabatan' =>
                    $validated['jabatan'],

                'foto' =>
                    $fotoPath,

                'urutan' =>
                    $validated['urutan'] ??
                    0,

                'is_active' =>
                    array_key_exists(
                        'is_active',
                        $validated
                    )
                        ? (bool) $validated['is_active']
                        : true,

                'updated_by' =>
                    auth()->id(),
            ]);

        $perangkat->load(
            'updatedBy:id,name'
        );

        return response()->json([
            'message' =>
                'Perangkat desa berhasil ditambahkan.',

            'data' =>
                new PerangkatDesaResource(
                    $perangkat
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    |
    | GET /api/admin/perangkat-desa/{perangkatDesa}
    |
    */

    public function show(
        PerangkatDesa $perangkatDesa
    ): JsonResponse {
        $perangkatDesa->load(
            'updatedBy:id,name'
        );

        return response()->json([
            'message' =>
                'Detail perangkat desa berhasil diambil.',

            'data' =>
                new PerangkatDesaResource(
                    $perangkatDesa
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    |
    | POST /api/admin/perangkat-desa/{perangkatDesa}
    |
    | Foto baru OPTIONAL.
    |
    | Kalau foto tidak dikirim:
    | foto lama tetap digunakan.
    |
    */

    public function update(
        Request $request,
        PerangkatDesa $perangkatDesa
    ): JsonResponse {
        $validated =
            $request->validate([
                'nama' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'jabatan' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'foto' => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120',
                ],

                'urutan' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | DATA DASAR
        |--------------------------------------------------------------------------
        */

        $perangkatDesa->nama =
            $validated['nama'];

        $perangkatDesa->jabatan =
            $validated['jabatan'];

        if (
            array_key_exists(
                'urutan',
                $validated
            )
        ) {
            $perangkatDesa->urutan =
                $validated['urutan'];
        }

        if (
            array_key_exists(
                'is_active',
                $validated
            )
        ) {
            $perangkatDesa->is_active =
                (bool) $validated['is_active'];
        }

        /*
        |--------------------------------------------------------------------------
        | FOTO BARU
        |--------------------------------------------------------------------------
        */

        if (
            $request->hasFile('foto')
        ) {
            /*
            |--------------------------------------------------------------------------
            | Simpan foto baru terlebih dahulu
            |--------------------------------------------------------------------------
            |
            | Kita simpan dulu supaya kalau proses storage gagal,
            | foto lama tidak langsung hilang.
            |
            */

            $newFotoPath =
                $request
                    ->file('foto')
                    ->store(
                        'perangkat-desa',
                        'local'
                    );

            $oldFotoPath =
                $perangkatDesa->foto;

            $perangkatDesa->foto =
                $newFotoPath;

            /*
            |--------------------------------------------------------------------------
            | Hapus foto lama
            |--------------------------------------------------------------------------
            */

            if (
                filled($oldFotoPath) &&
                Storage::disk('local')
                    ->exists($oldFotoPath)
            ) {
                Storage::disk('local')
                    ->delete(
                        $oldFotoPath
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AUDIT
        |--------------------------------------------------------------------------
        */

        $perangkatDesa->updated_by =
            auth()->id();

        $perangkatDesa->save();

        $perangkatDesa->load(
            'updatedBy:id,name'
        );

        return response()->json([
            'message' =>
                'Perangkat desa berhasil diperbarui.',

            'data' =>
                new PerangkatDesaResource(
                    $perangkatDesa
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    |
    | DELETE /api/admin/perangkat-desa/{perangkatDesa}
    |
    */

    public function destroy(
        PerangkatDesa $perangkatDesa
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | HAPUS FOTO
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $perangkatDesa->foto
            ) &&
            Storage::disk('local')
                ->exists(
                    $perangkatDesa->foto
                )
        ) {
            Storage::disk('local')
                ->delete(
                    $perangkatDesa->foto
                );
        }

        /*
        |--------------------------------------------------------------------------
        | HAPUS DATABASE
        |--------------------------------------------------------------------------
        */

        $perangkatDesa->delete();

        return response()->json([
            'message' =>
                'Perangkat desa berhasil dihapus.',

            'data' =>
                null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE ACTIVE
    |--------------------------------------------------------------------------
    |
    | PATCH /api/admin/perangkat-desa/{perangkatDesa}/active
    |
    */

    public function toggleActive(
        PerangkatDesa $perangkatDesa
    ): JsonResponse {
        $perangkatDesa->is_active =
            ! $perangkatDesa->is_active;

        $perangkatDesa->updated_by =
            auth()->id();

        $perangkatDesa->save();

        $perangkatDesa->load(
            'updatedBy:id,name'
        );

        return response()->json([
            'message' =>
                $perangkatDesa->is_active
                    ? 'Perangkat desa berhasil diaktifkan.'
                    : 'Perangkat desa berhasil dinonaktifkan.',

            'data' =>
                new PerangkatDesaResource(
                    $perangkatDesa
                ),
        ]);
    }
}