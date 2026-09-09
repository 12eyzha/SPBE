<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanSkuRequest;
use App\Http\Resources\PengajuanSkuResource;
use App\Models\PengajuanSku;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PengajuanSkuController extends Controller
{
    /**
     * Status yang masih dianggap aktif.
     *
     * User tidak boleh membuat pengajuan SKU baru
     * selama masih memiliki pengajuan:
     *
     * - menunggu_verifikasi
     * - diproses
     *
     * Status disetujui dicek secara terpisah
     * berdasarkan expired_at.
     */
    private const ACTIVE_STATUSES = [
        'menunggu_verifikasi',
        'diproses',
    ];

    /**
     * Menampilkan seluruh riwayat pengajuan SKU
     * milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        $pengajuan = PengajuanSku::query()
            ->where(
                'user_id',
                $userId
            )
            ->with([
                'dokumen',

                'riwayat' =>
                    fn ($query) =>
                        $query->latest(),
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' =>
                'Daftar pengajuan SKU berhasil diambil.',

            'data' =>
                PengajuanSkuResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /**
     * Menyimpan pengajuan SKU baru.
     *
     * User tidak boleh membuat pengajuan baru jika:
     *
     * 1. Masih memiliki pengajuan:
     *    - menunggu_verifikasi
     *    - diproses
     *
     * ATAU
     *
     * 2. Memiliki pengajuan:
     *    - disetujui
     *
     *    dan:
     *    - expired_at NULL, atau
     *    - expired_at masih lebih besar dari waktu sekarang.
     *
     * Pengajuan:
     * - ditolak
     * - disetujui tetapi sudah expired
     *
     * tidak dianggap aktif.
     *
     * Jika status disetujui memiliki expired_at NULL,
     * pengajuan tetap dianggap aktif sebagai fallback keamanan.
     */
    public function store(
        StorePengajuanSkuRequest $request,
        AuditLogService $auditLogService
    ): JsonResponse {
        $validated =
            $request->validated();

        $user =
            $request->user();

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
                        | Cek Pengajuan Aktif
                        |--------------------------------------------------------------------------
                        |
                        | Lock record terakhir yang masih aktif untuk
                        | mencegah duplicate submit secara bersamaan.
                        |
                        | Kondisi aktif:
                        |
                        | - menunggu_verifikasi
                        | - diproses
                        | - disetujui + expired_at NULL
                        | - disetujui + expired_at belum lewat
                        |
                        */

                        $existingActiveSubmission =
                            PengajuanSku::query()
                                ->where(
                                    'user_id',
                                    $user->id
                                )
                                ->where(
                                    function ($query) {
                                        /*
                                        |--------------------------------------------------------------------------
                                        | Status Menunggu / Diproses
                                        |--------------------------------------------------------------------------
                                        */

                                        $query->whereIn(
                                            'status',
                                            self::ACTIVE_STATUSES
                                        );

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Status Disetujui
                                        |--------------------------------------------------------------------------
                                        |
                                        | Jika expired_at NULL, anggap masih aktif.
                                        | Ini menjadi fallback keamanan.
                                        |
                                        | Jika expired_at > now(), masih aktif.
                                        |
                                        */

                                        $query->orWhere(
                                            function (
                                                $approvedQuery
                                            ) {
                                                $approvedQuery
                                                    ->where(
                                                        'status',
                                                        'disetujui'
                                                    )
                                                    ->where(
                                                        function (
                                                            $dateQuery
                                                        ) {
                                                            $dateQuery
                                                                ->whereNull(
                                                                    'expired_at'
                                                                )
                                                                ->orWhere(
                                                                    'expired_at',
                                                                    '>',
                                                                    now()
                                                                );
                                                        }
                                                    );
                                            }
                                        );
                                    }
                                )
                                ->latest('id')
                                ->lockForUpdate()
                                ->first();

                        /*
                        |--------------------------------------------------------------------------
                        | Tolak Pengajuan Baru
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $existingActiveSubmission
                        ) {
                            /*
                            |----------------------------------------------------------------------
                            | Approved masih aktif
                            |----------------------------------------------------------------------
                            */

                            if (
                                $existingActiveSubmission->status ===
                                'disetujui'
                            ) {
                                $expiredAt =
                                    $existingActiveSubmission
                                        ->expired_at;

                                /*
                                |------------------------------------------------------------------
                                | Approved dengan expired_at
                                |------------------------------------------------------------------
                                */

                                if ($expiredAt) {
                                    abort(
                                        422,
                                        'Anda masih memiliki pengajuan SKU yang disetujui dan masih berlaku hingga ' .
                                        $expiredAt->translatedFormat(
                                            'd F Y H:i'
                                        ) .
                                        '.'
                                    );
                                }

                                /*
                                |------------------------------------------------------------------
                                | Approved tanpa expired_at
                                |
                                | Jangan izinkan bypass dengan data tanggal NULL.
                                |------------------------------------------------------------------
                                */

                                abort(
                                    422,
                                    'Anda masih memiliki pengajuan SKU yang disetujui dan masih aktif.'
                                );
                            }

                            /*
                            |----------------------------------------------------------------------
                            | Menunggu / Diproses
                            |----------------------------------------------------------------------
                            */

                            abort(
                                422,
                                'Anda masih memiliki pengajuan SKU yang sedang diproses.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Buat Pengajuan
                        |--------------------------------------------------------------------------
                        */

                        $pengajuan =
                            PengajuanSku::create([
                                'user_id' =>
                                    $user->id,

                                'nik' =>
                                    $validated[
                                        'nik'
                                    ],

                                'nama_lengkap' =>
                                    $validated[
                                        'nama_lengkap'
                                    ],

                                'nomor_kk' =>
                                    $validated[
                                        'nomor_kk'
                                    ],

                                'tempat_lahir' =>
                                    $validated[
                                        'tempat_lahir'
                                    ],

                                'tanggal_lahir' =>
                                    $validated[
                                        'tanggal_lahir'
                                    ],

                                'jenis_kelamin' =>
                                    $validated[
                                        'jenis_kelamin'
                                    ],

                                'alamat' =>
                                    $validated[
                                        'alamat'
                                    ],

                                'rt' =>
                                    $validated[
                                        'rt'
                                    ],

                                'rw' =>
                                    $validated[
                                        'rw'
                                    ],

                                'kode_pos' =>
                                    $validated[
                                        'kode_pos'
                                    ],

                                'nama_usaha' =>
                                    $validated[
                                        'nama_usaha'
                                    ],

                                'jenis_usaha' =>
                                    $validated[
                                        'jenis_usaha'
                                    ],

                                'deskripsi_usaha' =>
                                    $validated[
                                        'deskripsi_usaha'
                                    ],

                                'alamat_usaha' =>
                                    $validated[
                                        'alamat_usaha'
                                    ],

                                'rt_usaha' =>
                                    $validated[
                                        'rt_usaha'
                                    ],

                                'rw_usaha' =>
                                    $validated[
                                        'rw_usaha'
                                    ],

                                'lama_menjalankan_usaha' =>
                                    $validated[
                                        'lama_menjalankan_usaha'
                                    ],

                                'perkiraan_penghasilan_per_bulan' =>
                                    $validated[
                                        'perkiraan_penghasilan_per_bulan'
                                    ],

                                'status' =>
                                    'menunggu_verifikasi',
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Simpan Dokumen
                        |--------------------------------------------------------------------------
                        */

                        $storedFilePaths =
                            $this->storeDocuments(
                                $pengajuan,
                                $request->file(
                                    'dokumen',
                                    []
                                )
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
                                    'Pengajuan SKU berhasil dibuat.',

                                'changed_by' =>
                                    $user->id,
                            ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Load Response
                        |--------------------------------------------------------------------------
                        */

                        return $pengajuan->load([
                            'dokumen',

                            'riwayat' =>
                                fn ($query) =>
                                    $query->latest(),
                        ]);
                    }
                );

            /*
            |--------------------------------------------------------------------------
            | Audit Log
            |--------------------------------------------------------------------------
            */

            $auditLogService->record(
                $request,
                'create',
                'pengajuan_sku',
                "Pengajuan SKU #{$pengajuan->id} berhasil dibuat oleh {$user->name}.",
                $pengajuan,
                [],
                [
                    'id' =>
                        $pengajuan->id,

                    'status' =>
                        $pengajuan->status,

                    'documents_count' =>
                        $pengajuan
                            ->dokumen
                            ->count(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Response Berhasil
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'message' =>
                    'Pengajuan SKU berhasil dikirim.',

                'data' =>
                    new PengajuanSkuResource(
                        $pengajuan
                    ),
            ], 201);
        } catch (
            Throwable $e
        ) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup File Jika Transaksi Gagal
            |--------------------------------------------------------------------------
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

            /*
            |--------------------------------------------------------------------------
            | HTTP Exception
            |--------------------------------------------------------------------------
            */

            if (
                $e instanceof
                \Symfony\Component\HttpKernel\Exception\HttpException
            ) {
                return response()->json([
                    'message' =>
                        $e->getMessage(),
                ], $e->getStatusCode());
            }

            /*
            |--------------------------------------------------------------------------
            | Server Error
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'message' =>
                    'Pengajuan SKU gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengajuan SKU milik user.
     */
    public function show(
        PengajuanSku $pengajuanSku
    ): JsonResponse {
        abort_unless(
            $pengajuanSku->user_id ===
                auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengajuan ini.'
        );

        $pengajuanSku->load([
            'dokumen',

            'riwayat.changedBy',
        ]);

        return response()->json([
            'message' =>
                'Detail pengajuan SKU berhasil diambil.',

            'data' =>
                new PengajuanSkuResource(
                    $pengajuanSku
                ),
        ]);
    }

    /**
     * Menyimpan dokumen SKU.
     *
     * @param array<string, UploadedFile|null> $documents
     * @return array<int, string>
     */
    private function storeDocuments(
        PengajuanSku $pengajuan,
        array $documents
    ): array {
        $jenisDokumen = [
            'ktp' =>
                'ktp',

            'kk' =>
                'kk',

            'foto_tempat_usaha' =>
                'foto_tempat_usaha',
        ];

        $storedFilePaths = [];

        foreach (
            $jenisDokumen as
            $input => $jenis
        ) {
            $file =
                $documents[$input]
                ?? null;

            if (
                ! $file
                instanceof UploadedFile
            ) {
                continue;
            }

            $path =
                $file->store(
                    'pengajuan-sku/' .
                    $pengajuan->id,
                    'local'
                );

            $storedFilePaths[] =
                $path;

            $pengajuan
                ->dokumen()
                ->create([
                    'jenis_dokumen' =>
                        $jenis,

                    'file_path' =>
                        $path,

                    'nama_file' =>
                        $file
                            ->getClientOriginalName(),
                ]);
        }

        return $storedFilePaths;
    }
}