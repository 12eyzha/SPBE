<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaduanRequest;
use App\Http\Resources\PengaduanResource;
use App\Models\Pengaduan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PengaduanController extends Controller
{
    /**
     * Menampilkan daftar pengaduan milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $pengaduan = Pengaduan::query()
            ->where(
                'user_id',
                auth()->id()
            )
            ->with([
                'respon' =>
                    fn ($query) =>
                        $query->latest(),

                'dokumen',

                'riwayat' =>
                    fn ($query) =>
                        $query->latest(),
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' =>
                'Daftar pengaduan berhasil diambil.',

            'data' =>
                PengaduanResource::collection(
                    $pengaduan
                ),
        ]);
    }

    /**
     * Menyimpan pengaduan baru.
     *
     * Pengaduan baru otomatis memiliki:
     *
     * - status = terkirim
     * - riwayat awal = terkirim
     *
     * Semua file yang berhasil disimpan dicatat sehingga
     * dapat dibersihkan apabila transaksi gagal.
     */
    public function store(
        StorePengaduanRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        $user =
            $request->user();

        $storedFilePaths = [];

        try {
            $pengaduan =
                DB::transaction(
                    function () use (
                        $request,
                        $validated,
                        $user,
                        &$storedFilePaths
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Buat Pengaduan
                        |--------------------------------------------------------------------------
                        */

                        $pengaduan =
                            Pengaduan::create([
                                'user_id' =>
                                    $user->id,

                                'nama' =>
                                    $validated[
                                        'nama'
                                    ],

                                'nomor' =>
                                    $validated[
                                        'nomor'
                                    ],

                                'subjek' =>
                                    $validated[
                                        'subjek'
                                    ],

                                'keterangan' =>
                                    $validated[
                                        'keterangan'
                                    ],

                                'lokasi' =>
                                    $validated[
                                        'lokasi'
                                    ],

                                'rt' =>
                                    $validated[
                                        'rt'
                                    ],

                                'rw' =>
                                    $validated[
                                        'rw'
                                    ],

                                'status' =>
                                    'terkirim',
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Foto Bukti
                        |--------------------------------------------------------------------------
                        */

                        $this->storeFotoBukti(
                            $pengaduan,
                            $request->file(
                                'foto_bukti'
                            ),
                            $storedFilePaths
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Dokumen Pendukung
                        |--------------------------------------------------------------------------
                        */

                        $this->storeDocuments(
                            $pengaduan,
                            $request->file(
                                'dokumen',
                                []
                            ),
                            $storedFilePaths
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Riwayat Awal
                        |--------------------------------------------------------------------------
                        */

                        $pengaduan
                            ->riwayat()
                            ->create([
                                'status' =>
                                    'terkirim',

                                'catatan' =>
                                    'Pengaduan berhasil dikirim.',

                                'changed_by' =>
                                    $user->id,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Load Response
                        |--------------------------------------------------------------------------
                        */

                        return $pengaduan->load([
                            'respon' =>
                                fn ($query) =>
                                    $query->latest(),

                            'dokumen',

                            'riwayat' =>
                                fn ($query) =>
                                    $query->latest(),
                        ]);
                    }
                );

            return response()->json([
                'message' =>
                    'Pengaduan berhasil dikirim.',

                'data' =>
                    new PengaduanResource(
                        $pengaduan
                    ),
            ], 201);
        } catch (
            Throwable $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup File
            |--------------------------------------------------------------------------
            |
            | Database transaction dapat rollback, tetapi file yang sudah
            | tersimpan di local storage tidak otomatis ikut terhapus.
            |
            */

            if (
                ! empty(
                    $storedFilePaths
                )
            ) {
                Storage::disk(
                    'local'
                )->delete(
                    $storedFilePaths
                );
            }

            report($e);

            return response()->json([
                'message' =>
                    'Pengaduan gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengaduan milik user.
     */
    public function show(
        Pengaduan $pengaduan
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Ownership Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $pengaduan->user_id ===
                auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengaduan ini.'
        );

        /*
        |--------------------------------------------------------------------------
        | Load Relations
        |--------------------------------------------------------------------------
        */

        $pengaduan->load([
            'respon' =>
                fn ($query) =>
                    $query->latest(),

            'dokumen',

            'riwayat' =>
                fn ($query) =>
                    $query->latest(),
        ]);

        return response()->json([
            'message' =>
                'Detail pengaduan berhasil diambil.',

            'data' =>
                new PengaduanResource(
                    $pengaduan
                ),
        ]);
    }

    /**
     * Menyimpan foto bukti pengaduan.
     *
     * @param array<int, string> $storedFilePaths
     */
    private function storeFotoBukti(
        Pengaduan $pengaduan,
        ?UploadedFile $file,
        array &$storedFilePaths
    ): void {
        if (
            ! $file instanceof UploadedFile
        ) {
            return;
        }

        $path =
            $file->store(
                'pengaduan/' .
                $pengaduan->id .
                '/foto-bukti',
                'local'
            );

        $storedFilePaths[] =
            $path;

        $pengaduan->update([
            'foto_bukti' =>
                $path,
        ]);
    }

    /**
     * Menyimpan dokumen pendukung pengaduan.
     *
     * @param array<int, UploadedFile|null> $documents
     * @param array<int, string> $storedFilePaths
     */
    private function storeDocuments(
        Pengaduan $pengaduan,
        array $documents,
        array &$storedFilePaths
    ): void {
        foreach (
            $documents as $file
        ) {
            if (
                ! $file instanceof UploadedFile
            ) {
                continue;
            }

            $path =
                $file->store(
                    'pengaduan/' .
                    $pengaduan->id .
                    '/dokumen',
                    'local'
                );

            $storedFilePaths[] =
                $path;

            $pengaduan
                ->dokumen()
                ->create([
                    'file_path' =>
                        $path,

                    'nama_file' =>
                        $file
                            ->getClientOriginalName(),
                ]);
        }
    }
}