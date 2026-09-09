<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanUmkmRequest;
use App\Http\Requests\UpdatePengajuanUmkmRequest;
use App\Http\Resources\PengajuanUmkmResource;
use App\Models\PengajuanUmkm;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class PengajuanUmkmController extends Controller
{
    /**
     * Menampilkan daftar pengajuan UMKM
     * milik user yang sedang login.
     *
     * Data soft deleted tidak ikut ditampilkan.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanUmkm::query()
            ->where(
                'user_id',
                auth()->id()
            )
            ->with([
                'kategori',

                'foto',

                'riwayat.changedBy' =>
                    fn ($query) =>
                        $query->latest(),
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' =>
                'Daftar pengajuan UMKM berhasil diambil.',

            'data' =>
                PengajuanUmkmResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /**
     * Menyimpan pengajuan UMKM baru.
     *
     * Pengajuan baru:
     * - status = menunggu_verifikasi
     * - is_active = false
     * - memiliki riwayat awal
     * - foto tersimpan di private storage
     */
    public function store(
        StorePengajuanUmkmRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        $user =
            $request->user();

        /*
        |--------------------------------------------------------------------------
        | File Yang Sudah Tersimpan
        |--------------------------------------------------------------------------
        |
        | Digunakan untuk cleanup jika transaction gagal.
        |
        */

        $storedFilePaths = [];

        try {
            $pengajuan =
                DB::transaction(
                    function () use (
                        $request,
                        $validated,
                        $user,
                        &$storedFilePaths
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Buat Pengajuan
                        |--------------------------------------------------------------------------
                        */

                        $pengajuan =
                            PengajuanUmkm::create([
                                'user_id' =>
                                    $user->id,

                                'nama_umkm' =>
                                    $validated[
                                        'nama_umkm'
                                    ],

                                'kategori_id' =>
                                    $validated[
                                        'kategori_id'
                                    ],

                                'deskripsi_umkm' =>
                                    $validated[
                                        'deskripsi_umkm'
                                    ],

                                'harga_min' =>
                                    $validated[
                                        'harga_min'
                                    ],

                                'harga_max' =>
                                    $validated[
                                        'harga_max'
                                    ],

                                'alamat' =>
                                    $validated[
                                        'alamat'
                                    ],

                                'jam_buka_mulai' =>
                                    $validated[
                                        'jam_buka_mulai'
                                    ] ?? null,

                                'jam_buka_selesai' =>
                                    $validated[
                                        'jam_buka_selesai'
                                    ] ?? null,

                                'nomor_wa' =>
                                    $validated[
                                        'nomor_wa'
                                    ],

                                'link_ecommerce' =>
                                    $validated[
                                        'link_ecommerce'
                                    ] ?? null,

                                'status' =>
                                    'menunggu_verifikasi',

                                'is_active' =>
                                    false,

                                'catatan_admin' =>
                                    null,

                                'approved_by' =>
                                    null,

                                'approved_at' =>
                                    null,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Foto
                        |--------------------------------------------------------------------------
                        */

                        $this->storePhotos(
                            $pengajuan,
                            $request->file(
                                'foto',
                                []
                            ),
                            $storedFilePaths
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Riwayat Awal
                        |--------------------------------------------------------------------------
                        */

                        $pengajuan
                            ->riwayat()
                            ->create([
                                'status' =>
                                    'menunggu_verifikasi',

                                'catatan' =>
                                    'Pengajuan UMKM berhasil dibuat.',

                                'changed_by' =>
                                    $user->id,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Load Response
                        |--------------------------------------------------------------------------
                        */

                        return $pengajuan->load([
                            'kategori',

                            'foto',

                            'riwayat.changedBy' =>
                                fn ($query) =>
                                    $query->latest(),
                        ]);
                    }
                );

            return response()->json([
                'message' =>
                    'Pengajuan UMKM berhasil dikirim.',

                'data' =>
                    new PengajuanUmkmResource(
                        $pengajuan
                    ),
            ], 201);
        } catch (
            Throwable $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup File
            |--------------------------------------------------------------------------
            */

            $this->deleteStoredFiles(
                $storedFilePaths
            );

            report($e);

            return response()->json([
                'message' =>
                    'Pengajuan UMKM gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengajuan UMKM
     * milik user yang sedang login.
     */
    public function show(
        PengajuanUmkm $pengajuanUmkm
    ): JsonResponse {
        $this->ensureOwnership(
            $pengajuanUmkm
        );

        $pengajuanUmkm->load([
            'kategori',

            'foto',

            'riwayat.changedBy' =>
                fn ($query) =>
                    $query->latest(),
        ]);

        return response()->json([
            'message' =>
                'Detail pengajuan UMKM berhasil diambil.',

            'data' =>
                new PengajuanUmkmResource(
                    $pengajuanUmkm
                ),
        ]);
    }

    /**
     * Memperbarui data UMKM milik user.
     *
     * Setelah edit:
     * - status = menunggu_verifikasi
     * - is_active = false
     * - approved_by = null
     * - approved_at = null
     *
     * existing_foto_ids berisi foto lama
     * yang tetap dipertahankan.
     */
    public function update(
        UpdatePengajuanUmkmRequest $request,
        PengajuanUmkm $pengajuanUmkm,
        AuditLogService $auditLogService
    ): JsonResponse {
        $this->ensureOwnership(
            $pengajuanUmkm
        );

        $validated =
            $request->validated();

        $storedFilePaths = [];
        $deletedFilePaths = [];

        try {
            $pengajuanUmkm =
                DB::transaction(
                    function () use (
                        $request,
                        $validated,
                        $pengajuanUmkm,
                        $auditLogService,
                        &$storedFilePaths,
                        &$deletedFilePaths
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock Record
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanUmkm =
                            PengajuanUmkm::query()
                                ->whereKey(
                                    $pengajuanUmkm->id
                                )
                                ->where(
                                    'user_id',
                                    $request
                                        ->user()
                                        ->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        /*
                        |--------------------------------------------------------------------------
                        | Data Lama
                        |--------------------------------------------------------------------------
                        */

                        $oldValues = [
                            'nama_umkm' =>
                                $pengajuanUmkm
                                    ->nama_umkm,

                            'kategori_id' =>
                                $pengajuanUmkm
                                    ->kategori_id,

                            'deskripsi_umkm' =>
                                $pengajuanUmkm
                                    ->deskripsi_umkm,

                            'harga_min' =>
                                $pengajuanUmkm
                                    ->harga_min,

                            'harga_max' =>
                                $pengajuanUmkm
                                    ->harga_max,

                            'alamat' =>
                                $pengajuanUmkm
                                    ->alamat,

                            'jam_buka_mulai' =>
                                $pengajuanUmkm
                                    ->jam_buka_mulai
                                    ?->format(
                                        'H:i'
                                    ),

                            'jam_buka_selesai' =>
                                $pengajuanUmkm
                                    ->jam_buka_selesai
                                    ?->format(
                                        'H:i'
                                    ),

                            'nomor_wa' =>
                                $pengajuanUmkm
                                    ->nomor_wa,

                            'link_ecommerce' =>
                                $pengajuanUmkm
                                    ->link_ecommerce,

                            'status' =>
                                $pengajuanUmkm
                                    ->status,

                            'is_active' =>
                                $pengajuanUmkm
                                    ->is_active,
                        ];

                        /*
                        |--------------------------------------------------------------------------
                        | Foto Lama Yang Dipertahankan
                        |--------------------------------------------------------------------------
                        */

                        $existingFotoIds =
                            array_map(
                                'intval',
                                $validated[
                                    'existing_foto_ids'
                                ] ?? []
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Ambil Foto Yang Akan Dihapus
                        |--------------------------------------------------------------------------
                        */

                        $photosToDelete =
                            $pengajuanUmkm
                                ->foto()
                                ->when(
                                    empty(
                                        $existingFotoIds
                                    ),
                                    fn ($query) =>
                                        $query,
                                    fn ($query) =>
                                        $query->whereNotIn(
                                            'id',
                                            $existingFotoIds
                                        )
                                )
                                ->get();

                        foreach (
                            $photosToDelete as $photo
                        ) {
                            $deletedFilePaths[] =
                                $photo->file_path;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Hapus Metadata Foto
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $photosToDelete->isNotEmpty()
                        ) {
                            $photosToDelete->each(
                                fn ($photo) =>
                                    $photo->delete()
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Foto Baru
                        |--------------------------------------------------------------------------
                        */

                        $newPhotos =
                            $request->file(
                                'foto',
                                []
                            );

                        foreach (
                            $newPhotos as $file
                        ) {
                            if (
                                ! $file instanceof UploadedFile
                            ) {
                                continue;
                            }

                            $path =
                                $file->store(
                                    'pengajuan-umkm/' .
                                    $pengajuanUmkm->id,
                                    'local'
                                );

                            $storedFilePaths[] =
                                $path;

                            $pengajuanUmkm
                                ->foto()
                                ->create([
                                    'file_path' =>
                                        $path,

                                    /*
                                    |--------------------------------------------------
                                    | Gunakan urutan sementara.
                                    | Nanti dinormalisasi.
                                    |--------------------------------------------------
                                    */

                                    'urutan' =>
                                        255,
                                ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Normalisasi Urutan Foto
                        |--------------------------------------------------------------------------
                        */

                        $this->normalizePhotoOrder(
                            $pengajuanUmkm
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Update Data
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanUmkm->update([
                            'nama_umkm' =>
                                $validated[
                                    'nama_umkm'
                                ],

                            'kategori_id' =>
                                $validated[
                                    'kategori_id'
                                ],

                            'deskripsi_umkm' =>
                                $validated[
                                    'deskripsi_umkm'
                                ],

                            'harga_min' =>
                                $validated[
                                    'harga_min'
                                ],

                            'harga_max' =>
                                $validated[
                                    'harga_max'
                                ],

                            'alamat' =>
                                $validated[
                                    'alamat'
                                ],

                            'jam_buka_mulai' =>
                                $validated[
                                    'jam_buka_mulai'
                                ] ?? null,

                            'jam_buka_selesai' =>
                                $validated[
                                    'jam_buka_selesai'
                                ] ?? null,

                            'nomor_wa' =>
                                $validated[
                                    'nomor_wa'
                                ],

                            'link_ecommerce' =>
                                $validated[
                                    'link_ecommerce'
                                ] ?? null,

                            'status' =>
                                'menunggu_verifikasi',

                            'is_active' =>
                                false,

                            'catatan_admin' =>
                                null,

                            'approved_by' =>
                                null,

                            'approved_at' =>
                                null,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Riwayat
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanUmkm
                            ->riwayat()
                            ->create([
                                'status' =>
                                    'menunggu_verifikasi',

                                'catatan' =>
                                    'Data UMKM diperbarui oleh pemilik dan menunggu verifikasi ulang.',

                                'changed_by' =>
                                    $request
                                        ->user()
                                        ->id,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Audit Log
                        |--------------------------------------------------------------------------
                        */

                        $auditLogService->record(
                            $request,
                            'update_umkm',
                            'pengajuan_umkm',
                            "Data UMKM #{$pengajuanUmkm->id} diperbarui oleh pemilik.",
                            $pengajuanUmkm,
                            $oldValues,
                            [
                                'nama_umkm' =>
                                    $pengajuanUmkm
                                        ->nama_umkm,

                                'kategori_id' =>
                                    $pengajuanUmkm
                                        ->kategori_id,

                                'deskripsi_umkm' =>
                                    $pengajuanUmkm
                                        ->deskripsi_umkm,

                                'harga_min' =>
                                    $pengajuanUmkm
                                        ->harga_min,

                                'harga_max' =>
                                    $pengajuanUmkm
                                        ->harga_max,

                                'alamat' =>
                                    $pengajuanUmkm
                                        ->alamat,

                                'jam_buka_mulai' =>
                                    $pengajuanUmkm
                                        ->jam_buka_mulai
                                        ?->format(
                                            'H:i'
                                        ),

                                'jam_buka_selesai' =>
                                    $pengajuanUmkm
                                        ->jam_buka_selesai
                                        ?->format(
                                            'H:i'
                                        ),

                                'nomor_wa' =>
                                    $pengajuanUmkm
                                        ->nomor_wa,

                                'link_ecommerce' =>
                                    $pengajuanUmkm
                                        ->link_ecommerce,

                                'status' =>
                                    $pengajuanUmkm
                                        ->status,

                                'is_active' =>
                                    $pengajuanUmkm
                                        ->is_active,
                            ]
                        );

                        return $pengajuanUmkm->fresh([
                            'kategori',

                            'foto',

                            'riwayat.changedBy' =>
                                fn ($query) =>
                                    $query->latest(),
                        ]);
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Hapus File Lama
            |--------------------------------------------------------------------------
            |
            | Database sudah sukses, baru file lama dihapus.
            |
            */

            $this->deleteStoredFiles(
                $deletedFilePaths
            );

            return response()->json([
                'message' =>
                    'Data UMKM berhasil diperbarui dan menunggu verifikasi ulang.',

                'data' =>
                    new PengajuanUmkmResource(
                        $pengajuanUmkm
                    ),
            ]);
        } catch (
            Throwable $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Foto Baru
            |--------------------------------------------------------------------------
            */

            $this->deleteStoredFiles(
                $storedFilePaths
            );

            report($e);

            return response()->json([
                'message' =>
                    'Data UMKM gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Mengaktifkan / menonaktifkan UMKM.
     *
     * Hanya UMKM yang sudah disetujui
     * yang boleh diubah status aktifnya.
     */
    public function toggleActive(
        PengajuanUmkm $pengajuanUmkm,
        AuditLogService $auditLogService
    ): JsonResponse {
        $this->ensureOwnership(
            $pengajuanUmkm
        );

        try {
            $pengajuanUmkm =
                DB::transaction(
                    function () use (
                        $pengajuanUmkm,
                        $auditLogService
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock Record
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanUmkm =
                            PengajuanUmkm::query()
                                ->whereKey(
                                    $pengajuanUmkm->id
                                )
                                ->where(
                                    'user_id',
                                    auth()->id()
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        /*
                        |--------------------------------------------------------------------------
                        | Hanya UMKM Disetujui
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $pengajuanUmkm->status !==
                            'disetujui'
                        ) {
                            throw new HttpException(
                                422,
                                'UMKM hanya dapat diaktifkan atau dinonaktifkan setelah disetujui admin.'
                            );
                        }

                        $isActiveLama =
                            (bool) $pengajuanUmkm
                                ->is_active;

                        $isActiveBaru =
                            ! $isActiveLama;

                        /*
                        |--------------------------------------------------------------------------
                        | Update
                        |--------------------------------------------------------------------------
                        */

                        $pengajuanUmkm->update([
                            'is_active' =>
                                $isActiveBaru,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Audit
                        |--------------------------------------------------------------------------
                        */

                        $auditLogService->record(
                            request(),
                            'toggle_active',
                            'pengajuan_umkm',
                            $isActiveBaru
                                ? "UMKM #{$pengajuanUmkm->id} diaktifkan oleh pemilik."
                                : "UMKM #{$pengajuanUmkm->id} dinonaktifkan oleh pemilik.",
                            $pengajuanUmkm,
                            [
                                'is_active' =>
                                    $isActiveLama,
                            ],
                            [
                                'is_active' =>
                                    $isActiveBaru,
                            ]
                        );

                        return $pengajuanUmkm->fresh([
                            'kategori',

                            'foto',

                            'riwayat.changedBy' =>
                                fn ($query) =>
                                    $query->latest(),
                        ]);
                    }
                );

            return response()->json([
                'message' =>
                    $pengajuanUmkm->is_active
                        ? 'UMKM berhasil diaktifkan.'
                        : 'UMKM berhasil dinonaktifkan.',

                'data' =>
                    new PengajuanUmkmResource(
                        $pengajuanUmkm
                    ),
            ]);
        } catch (
            HttpException $e
        ) {
            return response()->json([
                'message' =>
                    $e->getMessage(),
            ], $e->getStatusCode());
        } catch (
            Throwable $e
        ) {
            report($e);

            return response()->json([
                'message' =>
                    'Status aktif UMKM gagal diperbarui.',
            ], 500);
        }
    }

    /**
     * Soft delete UMKM milik user.
     *
     * File fisik sengaja tidak dihapus.
     * Ini memungkinkan recovery di masa depan.
     */
    public function destroy(
        PengajuanUmkm $pengajuanUmkm,
        AuditLogService $auditLogService
    ): JsonResponse {
        $this->ensureOwnership(
            $pengajuanUmkm
        );

        try {
            DB::transaction(
                function () use (
                    $pengajuanUmkm,
                    $auditLogService
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock Record
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanUmkm =
                        PengajuanUmkm::query()
                            ->whereKey(
                                $pengajuanUmkm->id
                            )
                            ->where(
                                'user_id',
                                auth()->id()
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    $auditLogService->record(
                        request(),
                        'delete_umkm',
                        'pengajuan_umkm',
                        "UMKM #{$pengajuanUmkm->id} dihapus oleh pemilik.",
                        $pengajuanUmkm,
                        [
                            'status' =>
                                $pengajuanUmkm
                                    ->status,

                            'is_active' =>
                                $pengajuanUmkm
                                    ->is_active,

                            'deleted_at' =>
                                null,
                        ],
                        [
                            'status' =>
                                $pengajuanUmkm
                                    ->status,

                            'is_active' =>
                                false,
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Nonaktifkan
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanUmkm->update([
                        'is_active' =>
                            false,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Soft Delete
                    |--------------------------------------------------------------------------
                    */

                    $pengajuanUmkm->delete();
                }
            );

            return response()->json([
                'message' =>
                    'UMKM berhasil dihapus.',
            ]);
        } catch (
            Throwable $e
        ) {
            report($e);

            return response()->json([
                'message' =>
                    'UMKM gagal dihapus.',
            ], 500);
        }
    }

    /**
     * Menyimpan foto UMKM.
     *
     * @param array<int, UploadedFile|null> $photos
     * @param array<int, string> $storedFilePaths
     */
    private function storePhotos(
        PengajuanUmkm $pengajuan,
        array $photos,
        array &$storedFilePaths
    ): void {
        foreach (
            $photos as $index => $file
        ) {
            if (
                ! $file instanceof UploadedFile
            ) {
                continue;
            }

            $path =
                $file->store(
                    'pengajuan-umkm/' .
                    $pengajuan->id,
                    'local'
                );

            $storedFilePaths[] =
                $path;

            $pengajuan
                ->foto()
                ->create([
                    'file_path' =>
                        $path,

                    'urutan' =>
                        $index + 1,
                ]);
        }
    }

    /**
     * Menormalkan urutan foto menjadi 1..5.
     */
    private function normalizePhotoOrder(
        PengajuanUmkm $pengajuanUmkm
    ): void {
        $photos =
            $pengajuanUmkm
                ->foto()
                ->orderBy('urutan')
                ->orderBy('id')
                ->get();

        foreach (
            $photos as $index => $photo
        ) {
            $photo->update([
                'urutan' =>
                    $index + 1,
            ]);
        }
    }

    /**
     * Memastikan UMKM milik user yang sedang login.
     */
    private function ensureOwnership(
        PengajuanUmkm $pengajuanUmkm
    ): void {
        if (
            $pengajuanUmkm->user_id !==
            auth()->id()
        ) {
            abort(
                403,
                'Anda tidak memiliki akses ke UMKM ini.'
            );
        }
    }

    /**
     * Menghapus file dari local storage.
     *
     * @param array<int, string> $paths
     */
    private function deleteStoredFiles(
        array $paths
    ): void {
        $paths =
            array_values(
                array_filter(
                    $paths
                )
            );

        if (
            empty($paths)
        ) {
            return;
        }

        Storage::disk(
            'local'
        )->delete(
            $paths
        );
    }
}