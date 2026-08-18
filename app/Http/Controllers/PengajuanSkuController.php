<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanSkuRequest;
use App\Http\Resources\PengajuanSkuResource;
use App\Models\PengajuanSku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class PengajuanSkuController extends Controller
{
    /**
     * Menampilkan daftar pengajuan SKU milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanSku::query()
            ->where('user_id', auth()->id())
            ->with([
                'dokumen',
                'riwayat' => fn ($query) => $query->latest(),
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar pengajuan SKU berhasil diambil.',
            'data' => PengajuanSkuResource::collection($pengajuan),
        ]);
    }

    /**
     * Menyimpan pengajuan SKU baru.
     */
    public function store(StorePengajuanSkuRequest $request): JsonResponse
    {
        try {
            $pengajuan = DB::transaction(function () use ($request) {
                $pengajuan = PengajuanSku::create([
                    'user_id' => $request->user()->id,
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
                    'nama_usaha' => $request->nama_usaha,
                    'jenis_usaha' => $request->jenis_usaha,
                    'deskripsi_usaha' => $request->deskripsi_usaha,
                    'alamat_usaha' => $request->alamat_usaha,
                    'rt_usaha' => $request->rt_usaha,
                    'rw_usaha' => $request->rw_usaha,
                    'lama_menjalankan_usaha' => $request->lama_menjalankan_usaha,
                    'perkiraan_penghasilan_per_bulan' => $request->perkiraan_penghasilan_per_bulan,
                    'status' => 'menunggu_verifikasi',
                ]);

                $this->storeDocuments(
                    $pengajuan,
                    $request->file('dokumen', [])
                );

                $pengajuan->riwayat()->create([
                    'status' => 'menunggu_verifikasi',
                    'catatan' => 'Pengajuan SKU berhasil dibuat.',
                    'changed_by' => $request->user()->id,
                ]);

                return $pengajuan->load([
                    'dokumen',
                    'riwayat' => fn ($query) => $query->latest(),
                ]);
            });

            return response()->json([
                'message' => 'Pengajuan SKU berhasil dikirim.',
                'data' => new PengajuanSkuResource($pengajuan),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Pengajuan SKU gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengajuan SKU milik user.
     */
    public function show(PengajuanSku $pengajuanSku): JsonResponse
    {
        abort_unless(
            $pengajuanSku->user_id === auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengajuan ini.'
        );

        $pengajuanSku->load([
            'dokumen',
            'riwayat.changedBy',
        ]);

        return response()->json([
            'message' => 'Detail pengajuan SKU berhasil diambil.',
            'data' => new PengajuanSkuResource($pengajuanSku),
        ]);
    }

    /**
     * Menyimpan dokumen pengajuan SKU.
     *
     * @param array<string, UploadedFile|null> $documents
     */
    private function storeDocuments(
        PengajuanSku $pengajuan,
        array $documents
    ): void {
        $jenisDokumen = [
            'ktp' => 'ktp',
            'kk' => 'kk',
            'foto_tempat_usaha' => 'foto_tempat_usaha',
        ];

        foreach ($jenisDokumen as $input => $jenis) {
            $file = $documents[$input] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'pengajuan-sku/' . $pengajuan->id,
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