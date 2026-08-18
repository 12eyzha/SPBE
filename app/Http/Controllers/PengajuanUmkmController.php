<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanUmkmRequest;
use App\Http\Resources\PengajuanUmkmResource;
use App\Models\PengajuanUmkm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class PengajuanUmkmController extends Controller
{
    /**
     * Menampilkan daftar pengajuan UMKM milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $pengajuan = PengajuanUmkm::query()
            ->where('user_id', auth()->id())
            ->with([
                'kategori',
                'foto',
                'riwayat' => fn ($query) => $query->latest(),
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar pengajuan UMKM berhasil diambil.',
            'data' => PengajuanUmkmResource::collection($pengajuan),
        ]);
    }

    /**
     * Menyimpan pengajuan UMKM baru.
     */
    public function store(StorePengajuanUmkmRequest $request): JsonResponse
    {
        try {
            $pengajuan = DB::transaction(function () use ($request) {
                $pengajuan = PengajuanUmkm::create([
                    'user_id' => $request->user()->id,
                    'nama_umkm' => $request->nama_umkm,
                    'kategori_id' => $request->kategori_id,
                    'deskripsi_umkm' => $request->deskripsi_umkm,
                    'harga_min' => $request->harga_min,
                    'harga_max' => $request->harga_max,
                    'alamat' => $request->alamat,
                    'jam_buka_mulai' => $request->jam_buka_mulai,
                    'jam_buka_selesai' => $request->jam_buka_selesai,
                    'nomor_wa' => $request->nomor_wa,
                    'link_ecommerce' => $request->link_ecommerce,
                    'status' => 'menunggu_verifikasi',
                    'is_active' => true,
                ]);

                $this->storePhotos(
                    $pengajuan,
                    $request->file('foto', [])
                );

                $pengajuan->riwayat()->create([
                    'status' => 'menunggu_verifikasi',
                    'catatan' => 'Pengajuan UMKM berhasil dibuat.',
                    'changed_by' => $request->user()->id,
                ]);

                return $pengajuan->load([
                    'kategori',
                    'foto',
                    'riwayat' => fn ($query) => $query->latest(),
                ]);
            });

            return response()->json([
                'message' => 'Pengajuan UMKM berhasil dikirim.',
                'data' => new PengajuanUmkmResource($pengajuan),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Pengajuan UMKM gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengajuan UMKM milik user.
     */
    public function show(PengajuanUmkm $pengajuanUmkm): JsonResponse
    {
        abort_unless(
            $pengajuanUmkm->user_id === auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengajuan ini.'
        );

        $pengajuanUmkm->load([
            'kategori',
            'foto',
            'riwayat.changedBy',
        ]);

        return response()->json([
            'message' => 'Detail pengajuan UMKM berhasil diambil.',
            'data' => new PengajuanUmkmResource($pengajuanUmkm),
        ]);
    }

    /**
     * Menyimpan foto UMKM.
     *
     * @param array<int, UploadedFile|null> $photos
     */
    private function storePhotos(
        PengajuanUmkm $pengajuan,
        array $photos
    ): void {
        foreach ($photos as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'pengajuan-umkm/' . $pengajuan->id,
                'local'
            );

            $pengajuan->foto()->create([
                'file_path' => $path,
                'urutan' => $index + 1,
            ]);
        }
    }
}