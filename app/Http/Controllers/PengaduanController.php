<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengaduanRequest;
use App\Http\Resources\PengaduanResource;
use App\Models\Pengaduan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class PengaduanController extends Controller
{
    /**
     * Menampilkan daftar pengaduan milik user yang sedang login.
     */
    public function index(): JsonResponse
    {
        $pengaduan = Pengaduan::query()
            ->where('user_id', auth()->id())
            ->with([
                'respon',
                'dokumen',
            ])
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Daftar pengaduan berhasil diambil.',
            'data' => PengaduanResource::collection($pengaduan),
        ]);
    }

    /**
     * Menyimpan pengaduan baru.
     */
    public function store(StorePengaduanRequest $request): JsonResponse
    {
        try {
            $pengaduan = DB::transaction(function () use ($request) {
                $pengaduan = Pengaduan::create([
                    'user_id' => $request->user()->id,
                    'nama' => $request->nama,
                    'nomor' => $request->nomor,
                    'subjek' => $request->subjek,
                    'keterangan' => $request->keterangan,
                    'lokasi' => $request->lokasi,
                    'rt' => $request->rt,
                    'rw' => $request->rw,
                    'status' => 'terkirim',
                ]);

                $this->storeFotoBukti(
                    $pengaduan,
                    $request->file('foto_bukti')
                );

                $this->storeDocuments(
                    $pengaduan,
                    $request->file('dokumen', [])
                );

                return $pengaduan->load([
                    'respon',
                    'dokumen',
                ]);
            });

            return response()->json([
                'message' => 'Pengaduan berhasil dikirim.',
                'data' => new PengaduanResource($pengaduan),
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Pengaduan gagal diproses.',
            ], 500);
        }
    }

    /**
     * Menampilkan detail pengaduan milik user.
     */
    public function show(Pengaduan $pengaduan): JsonResponse
    {
        abort_unless(
            $pengaduan->user_id === auth()->id(),
            403,
            'Anda tidak memiliki akses ke pengaduan ini.'
        );

        $pengaduan->load([
            'respon',
            'dokumen',
        ]);

        return response()->json([
            'message' => 'Detail pengaduan berhasil diambil.',
            'data' => new PengaduanResource($pengaduan),
        ]);
    }

    /**
     * Menyimpan foto bukti pengaduan.
     */
    private function storeFotoBukti(
        Pengaduan $pengaduan,
        ?UploadedFile $file
    ): void {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $path = $file->store(
            'pengaduan/' . $pengaduan->id . '/foto-bukti',
            'local'
        );

        $pengaduan->update([
            'foto_bukti' => $path,
        ]);
    }

    /**
     * Menyimpan dokumen pendukung pengaduan.
     *
     * @param array<int, UploadedFile|null> $documents
     */
    private function storeDocuments(
        Pengaduan $pengaduan,
        array $documents
    ): void {
        foreach ($documents as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'pengaduan/' . $pengaduan->id . '/dokumen',
                'local'
            );

            $pengaduan->dokumen()->create([
                'file_path' => $path,
                'nama_file' => $file->getClientOriginalName(),
            ]);
        }
    }
}