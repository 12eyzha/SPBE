<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\PengaduanDokumen;
use App\Models\PengajuanKtpDokumen;
use App\Models\PengajuanSkuDokumen;
use App\Models\PengajuanUmkmFoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrivateFileController extends Controller
{
    /**
     * Menampilkan dokumen KTP milik user yang sedang login.
     */
    public function ktp(PengajuanKtpDokumen $dokumen): BinaryFileResponse
    {
        abort_unless(
            $dokumen->pengajuan()->where('user_id', auth()->id())->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan dokumen SKU milik user yang sedang login.
     */
    public function sku(PengajuanSkuDokumen $dokumen): BinaryFileResponse
    {
        abort_unless(
            $dokumen->pengajuan()->where('user_id', auth()->id())->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto UMKM milik user yang sedang login.
     */
    public function umkm(PengajuanUmkmFoto $foto): BinaryFileResponse
    {
        abort_unless(
            $foto->pengajuan()->where('user_id', auth()->id())->exists(),
            403,
            'Anda tidak memiliki akses ke foto ini.'
        );

        return $this->serveFile(
            $foto->file_path,
            'foto-umkm-' . $foto->urutan
        );
    }

    /**
     * Menampilkan dokumen pendukung pengaduan milik user yang sedang login.
     */
    public function pengaduan(PengaduanDokumen $dokumen): BinaryFileResponse
    {
        abort_unless(
            $dokumen->pengaduan()->where('user_id', auth()->id())->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto bukti pengaduan milik user yang sedang login.
     */
    public function pengaduanFoto(Pengaduan $pengaduan): BinaryFileResponse
    {
        abort_unless(
            $pengaduan->user_id === auth()->id(),
            403,
            'Anda tidak memiliki akses ke foto bukti ini.'
        );

        abort_unless(
            $pengaduan->foto_bukti !== null,
            404,
            'Foto bukti tidak ditemukan.'
        );

        return $this->serveFile(
            $pengaduan->foto_bukti,
            'foto-bukti-pengaduan-' . $pengaduan->id
        );
    }

    /**
     * Menampilkan dokumen KTP untuk Admin / Super Admin.
     */
    public function adminKtp(PengajuanKtpDokumen $dokumen): BinaryFileResponse
    {
        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan dokumen SKU untuk Admin / Super Admin.
     */
    public function adminSku(PengajuanSkuDokumen $dokumen): BinaryFileResponse
    {
        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto UMKM untuk Admin / Super Admin.
     */
    public function adminUmkm(PengajuanUmkmFoto $foto): BinaryFileResponse
    {
        return $this->serveFile(
            $foto->file_path,
            'foto-umkm-' . $foto->urutan
        );
    }

    /**
     * Menampilkan dokumen pendukung pengaduan untuk Admin / Super Admin.
     */
    public function adminPengaduan(
        PengaduanDokumen $dokumen
    ): BinaryFileResponse {
        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto bukti pengaduan untuk Admin / Super Admin.
     */
    public function adminPengaduanFoto(
        Pengaduan $pengaduan
    ): BinaryFileResponse {
        abort_unless(
            $pengaduan->foto_bukti !== null,
            404,
            'Foto bukti tidak ditemukan.'
        );

        return $this->serveFile(
            $pengaduan->foto_bukti,
            'foto-bukti-pengaduan-' . $pengaduan->id
        );
    }

    /**
     * Mengembalikan file dari private/local storage.
     */
    private function serveFile(
        string $path,
        string $filename
    ): BinaryFileResponse {
        $disk = Storage::disk('local');

        abort_unless(
            $disk->exists($path),
            404,
            'File tidak ditemukan.'
        );

        return response()->file(
            $disk->path($path),
            [
                'Content-Disposition' => 'inline; filename="' .
                    addslashes($filename) .
                    '"',
            ]
        );
    }
}