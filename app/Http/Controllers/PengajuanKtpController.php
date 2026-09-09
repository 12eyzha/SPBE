<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanKtpRequest;
use App\Http\Resources\PengajuanKtpResource;
use App\Models\PengajuanKtp;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PengajuanKtpController extends Controller
{
    /**
     * Status yang selalu dianggap aktif.
     *
     * Pengajuan dengan status ini selalu menghalangi
     * user membuat pengajuan KTP baru.
     */
    private const ACTIVE_STATUSES = [
        'menunggu_verifikasi',
        'diproses',
    ];

    /**
     * Menampilkan seluruh riwayat pengajuan KTP
     * milik user yang sedang login.
     *
     * Pengajuan expired tetap ditampilkan karena
     * merupakan bagian dari riwayat.
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        $pengajuan = PengajuanKtp::query()
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
                'Daftar pengajuan KTP berhasil diambil.',

            'data' =>
                PengajuanKtpResource::collection(
                    $pengajuan
                ),
        ]);
    }

    /**
     * Menyimpan pengajuan KTP baru.
     *
     * Pengajuan baru hanya boleh dibuat jika user:
     *
     * - tidak memiliki pengajuan menunggu_verifikasi;
     * - tidak memiliki pengajuan diproses;
     * - tidak memiliki pengajuan disetujui yang masih aktif.
     *
     * Pengajuan disetujui yang sudah expired tidak menghalangi
     * pengajuan baru.
     */
    public function store(
        StorePengajuanKtpRequest $request,
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
                        | Lock record agar dua request bersamaan tidak
                        | menghasilkan dua pengajuan baru sekaligus.
                        |
                        */

                        $existingActiveSubmission =
                            PengajuanKtp::query()
                                ->where(
                                    'user_id',
                                    $user->id
                                )
                                ->where(
                                    function ($query) {
                                        /*
                                        |------------------------------------------------------------------
                                        | Status yang selalu aktif
                                        |------------------------------------------------------------------
                                        */

                                        $query->whereIn(
                                            'status',
                                            self::ACTIVE_STATUSES
                                        );

                                        /*
                                        |------------------------------------------------------------------
                                        | Status disetujui
                                        |------------------------------------------------------------------
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
                        | Jika masih ada pengajuan aktif
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

                                if ($expiredAt) {
                                    abort(
                                        422,
                                        'Anda masih memiliki pengajuan KTP yang disetujui dan masih berlaku hingga ' .
                                        $expiredAt->translatedFormat(
                                            'd F Y H:i'
                                        ) .
                                        '.'
                                    );
                                }

                                /*
                                |--------------------------------------------------------------
                                | Approved tanpa expired_at
                                |
                                | Jangan izinkan bypass dengan data tanggal NULL.
                                |--------------------------------------------------------------
                                */

                                abort(
                                    422,
                                    'Anda masih memiliki pengajuan KTP yang disetujui dan masih aktif.'
                                );
                            }

                            /*
                            |----------------------------------------------------------------------
                            | Menunggu / Diproses
                            |----------------------------------------------------------------------
                            */

                            abort(
                                422,
                                'Anda masih memiliki pengajuan KTP yang sedang diproses.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Buat Pengajuan
                        |--------------------------------------------------------------------------
                        */

                        $pengajuan =
                            PengajuanKtp::create([
                                'user_id' =>
                                    $user->id,

                                'jenis_permohonan' =>
                                    $validated[
                                        'jenis_permohonan'
                                    ],

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

                                'keperluan' =>
                                    $validated[
                                        'keperluan'
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
                        | Riwayat Status Awal
                        |--------------------------------------------------------------------------
                        */

                        $pengajuan
                            ->riwayat()
                            ->create([
                                'status' =>
                                    'menunggu_verifikasi',

                                'catatan' =>
                                    'Pengajuan KTP berhasil dibuat.',

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
            |
            | Jangan mencatat data sensitif:
            | - NIK
            | - Nomor KK
            | - Alamat lengkap
            | - Isi dokumen
            |
            */

            $auditLogService->record(
                $request,
                'create',
                'pengajuan_ktp',
                "Pengajuan KTP #{$pengajuan->id} berhasil dibuat oleh {$user->name}.",
                $pengajuan,
                [],
                [
                    'id' =>
                        $pengajuan->id,

                    'jenis_permohonan' =>
                        $pengajuan->jenis_permohonan,

                    'status' =>
                        $pengajuan->status,

                    'documents_count' =>
                        $pengajuan
                            ->dokumen
                            ->count(),
                ]
            );

            return response()->json([
                'message' =>
                    'Pengajuan KTP berhasil dikirim.',

                'data' =>
                    new PengajuanKtpResource(
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
            | Generic Error
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'message' =>
                    'Pengajuan KTP gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengajuan KTP milik user.
     */
    public function show(
        PengajuanKtp $pengajuanKtp
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Ownership Check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $pengajuanKtp->user_id ===
                auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengajuan ini.'
        );

        $pengajuanKtp->load([
            'dokumen',

            'riwayat.changedBy',
        ]);

        return response()->json([
            'message' =>
                'Detail pengajuan KTP berhasil diambil.',

            'data' =>
                new PengajuanKtpResource(
                    $pengajuanKtp
                ),
        ]);
    }

    /**
     * Menyimpan dokumen pengajuan KTP.
     *
     * @param array<string, UploadedFile|null> $documents
     * @return array<int, string>
     */
    private function storeDocuments(
        PengajuanKtp $pengajuan,
        array $documents
    ): array {
        $jenisDokumen = [
            'kk' =>
                'kk',

            'akta_kelahiran' =>
                'akta_kelahiran',

            'ijazah' =>
                'ijazah',

            'ktp_lama' =>
                'ktp_lama',

            'pengantar_rt_rw' =>
                'pengantar_rt_rw',

            'surat_kehilangan_polsek' =>
                'surat_kehilangan_polsek',
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
                    'pengajuan-ktp/' .
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