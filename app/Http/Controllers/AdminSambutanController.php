<?php

namespace App\Http\Controllers;

use App\Models\Sambutan;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSambutanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Daftar / Ambil Sambutan
    |--------------------------------------------------------------------------
    |
    | Karena sambutan dibatasi maksimal 1 data, endpoint ini
    | cukup mengembalikan satu data sambutan.
    |
    */

    public function index(): JsonResponse
    {
        $sambutan = Sambutan::query()
            ->with([
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->first();

        return response()->json([
            'sambutan' => $sambutan,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tambah Sambutan
    |--------------------------------------------------------------------------
    |
    | Hanya boleh ada satu sambutan.
    |
    */

    public function store(
        Request $request,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Cek Apakah Sambutan Sudah Ada
        |--------------------------------------------------------------------------
        */

        $existingSambutan = Sambutan::query()->first();

        if ($existingSambutan) {
            return response()->json([
                'message' => 'Sambutan sudah tersedia. Silakan gunakan fitur edit.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'foto' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'nama' => [
                'required',
                'string',
                'max:255',
            ],
            'jabatan' => [
                'required',
                'string',
                'max:255',
            ],
            'text' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Upload Foto
        |--------------------------------------------------------------------------
        */

        $fotoPath = $request->file('foto')->store(
            'sambutan',
            'local'
        );

        /*
        |--------------------------------------------------------------------------
        | Buat Data Sambutan
        |--------------------------------------------------------------------------
        */

        $sambutan = Sambutan::create([
            'foto' => $fotoPath,
            'nama' => $validated['nama'],
            'jabatan' => $validated['jabatan'],
            'text' => $validated['text'],
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $sambutan->load([
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'create',
            'sambutan',
            "Sambutan Kepala Desa {$sambutan->nama} berhasil ditambahkan.",
            $sambutan,
            [],
            [
                'id' => $sambutan->id,
                'nama' => $sambutan->nama,
                'jabatan' => $sambutan->jabatan,
                'foto' => $sambutan->foto,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Sambutan berhasil ditambahkan.',
            'sambutan' => $sambutan,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Detail Sambutan
    |--------------------------------------------------------------------------
    */

    public function show(Sambutan $sambutan): JsonResponse
    {
        $sambutan->load([
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        return response()->json([
            'sambutan' => $sambutan,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Sambutan
    |--------------------------------------------------------------------------
    |
    | Foto baru bersifat opsional.
    |
    */

    public function update(
        Request $request,
        Sambutan $sambutan,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Validasi
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'nama' => [
                'required',
                'string',
                'max:255',
            ],
            'jabatan' => [
                'required',
                'string',
                'max:255',
            ],
            'text' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Simpan Data Lama
        |--------------------------------------------------------------------------
        */

        $oldValues = [
            'nama' => $sambutan->nama,
            'jabatan' => $sambutan->jabatan,
            'text' => $sambutan->text,
            'foto' => $sambutan->foto,
        ];

        /*
        |--------------------------------------------------------------------------
        | Ganti Foto Jika Ada
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('foto')) {
            /*
            |--------------------------------------------------------------------------
            | Hapus Foto Lama
            |--------------------------------------------------------------------------
            */

            if (
                $sambutan->foto &&
                Storage::disk('local')->exists($sambutan->foto)
            ) {
                Storage::disk('local')->delete(
                    $sambutan->foto
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan Foto Baru
            |--------------------------------------------------------------------------
            */

            $sambutan->foto = $request
                ->file('foto')
                ->store('sambutan', 'local');
        }

        /*
        |--------------------------------------------------------------------------
        | Update Data
        |--------------------------------------------------------------------------
        */

        $sambutan->nama = $validated['nama'];
        $sambutan->jabatan = $validated['jabatan'];
        $sambutan->text = $validated['text'];
        $sambutan->updated_by = $request->user()->id;

        $sambutan->save();

        $sambutan->load([
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Data Baru untuk Audit
        |--------------------------------------------------------------------------
        */

        $newValues = [
            'nama' => $sambutan->nama,
            'jabatan' => $sambutan->jabatan,
            'text' => $sambutan->text,
            'foto' => $sambutan->foto,
        ];

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'update',
            'sambutan',
            "Sambutan Kepala Desa {$sambutan->nama} diperbarui.",
            $sambutan,
            $oldValues,
            $newValues
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Sambutan berhasil diperbarui.',
            'sambutan' => $sambutan,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus Sambutan
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Sambutan $sambutan,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Simpan Data untuk Audit
        |--------------------------------------------------------------------------
        */

        $oldValues = [
            'id' => $sambutan->id,
            'nama' => $sambutan->nama,
            'jabatan' => $sambutan->jabatan,
            'text' => $sambutan->text,
            'foto' => $sambutan->foto,
        ];

        /*
        |--------------------------------------------------------------------------
        | Hapus File Foto
        |--------------------------------------------------------------------------
        */

        if (
            $sambutan->foto &&
            Storage::disk('local')->exists($sambutan->foto)
        ) {
            Storage::disk('local')->delete(
                $sambutan->foto
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Soft Delete
        |--------------------------------------------------------------------------
        */

        $sambutan->delete();

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'delete',
            'sambutan',
            "Sambutan Kepala Desa {$oldValues['nama']} dihapus.",
            $sambutan,
            $oldValues,
            []
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Sambutan berhasil dihapus.',
        ]);
    }
}