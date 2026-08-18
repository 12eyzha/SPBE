<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanKtpRequest;
use App\Http\Resources\PengajuanKtpResource;
use App\Models\PengajuanKtp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class PengajuanKtpController extends Controller
{
    /**
     * Menampilkan daftar pengajuan KTP milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanKtp::query()
            ->where('user_id', auth()->id())
            ->with([
                'dokumen',
                'riwayat' => fn ($query) => $query->latest(),
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar pengajuan KTP berhasil diambil.',
            'data' => PengajuanKtpResource::collection($pengajuan),
        ]);
    }

    /**
     * Menyimpan pengajuan KTP baru.
     */
    public function store(StorePengajuanKtpRequest $request): JsonResponse
    {
        try {
            $pengajuan = DB::transaction(function () use ($request) {
                $pengajuan = PengajuanKtp::create([
                    'user_id' => $request->user()->id,
                    'jenis_permohonan' => $request->jenis_permohonan,
                    'nik' => $request->nik,
                    'nama_lengkap' => $request->nama_lengkap,
                    'nomor_kk' => $request->nomor_kk,
                    'tempat_lahir' => $request->tempat_lahir,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'alamat' => $request->alamat,
                    'rt' => $request->rt,
                    'rw' => $request->rw,
                    'kode_pos' => $request->kode_pos,
                    'keperluan' => $request->keperluan,
                    'status' => 'menunggu_verifikasi',
                ]);

                $this->storeDocuments(
                    $pengajuan,
                    $request->file('dokumen', [])
                );

                $pengajuan->riwayat()->create([
                    'status' => 'menunggu_verifikasi',
                    'catatan' => 'Pengajuan KTP berhasil dibuat.',
                    'changed_by' => $request->user()->id,
                ]);

                return $pengajuan->load([
                    'dokumen',
                    'riwayat' => fn ($query) => $query->latest(),
                ]);
            });

            return response()->json([
                'message' => 'Pengajuan KTP berhasil dikirim.',
                'data' => new PengajuanKtpResource($pengajuan),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Pengajuan KTP gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengajuan KTP milik user.
     */
    public function show(PengajuanKtp $pengajuanKtp): JsonResponse
    {
        abort_unless(
            $pengajuanKtp->user_id === auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengajuan ini.'
        );

        $pengajuanKtp->load([
            'dokumen',
            'riwayat.changedBy',
        ]);

        return response()->json([
            'message' => 'Detail pengajuan KTP berhasil diambil.',
            'data' => new PengajuanKtpResource($pengajuanKtp),
        ]);
    }

    /**
     * Menyimpan dokumen pengajuan KTP.
     *
     * @param array<string, UploadedFile|null> $documents
     */
    private function storeDocuments(
        PengajuanKtp $pengajuan,
        array $documents
    ): void {
        $jenisDokumen = [
            'kk' => 'kk',
            'akta_kelahiran' => 'akta_kelahiran',
            'ijazah' => 'ijazah',
            'ktp_lama' => 'ktp_lama',
            'pengantar_rt_rw' => 'pengantar_rt_rw',
            'surat_kehilangan_polsek' => 'surat_kehilangan_polsek',
        ];

        foreach ($jenisDokumen as $input => $jenis) {
            $file = $documents[$input] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'pengajuan-ktp/' . $pengajuan->id,
                'local'
            );

            $pengajuan->dokumen()->create([
                'jenis_dokumen' => $jenis,
                'file_path' => $path,
                'nama_file' => $file->getClientOriginalName(),
            ]);
        }
    }
}