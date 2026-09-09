<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Galeri;
use App\Models\HeroSlide;
use App\Models\Pengaduan;
use App\Models\PengaduanDokumen;
use App\Models\PengajuanKtpDokumen;
use App\Models\PengajuanSkuDokumen;
use App\Models\PengajuanUmkmFoto;
use App\Models\PerangkatDesa;
use App\Models\Sambutan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrivateFileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    |
    | Route user dilindungi oleh:
    | auth:sanctum + active
    |
    | Setiap file tetap diperiksa ownership-nya.
    |
    */

    /**
     * Menampilkan dokumen KTP milik user yang sedang login.
     */
    public function ktp(
        PengajuanKtpDokumen $dokumen
    ): BinaryFileResponse {
        $this->authorizeKtpOwner($dokumen);

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan dokumen SKU milik user yang sedang login.
     */
    public function sku(
        PengajuanSkuDokumen $dokumen
    ): BinaryFileResponse {
        $this->authorizeSkuOwner($dokumen);

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto UMKM milik user yang sedang login.
     */
    public function umkm(
        PengajuanUmkmFoto $foto
    ): BinaryFileResponse {
        $this->authorizeUmkmOwner($foto);

        return $this->serveFile(
            $foto->file_path,
            'foto-umkm-' . $foto->urutan
        );
    }

    /**
     * Menampilkan dokumen pengaduan milik user yang sedang login.
     */
    public function pengaduan(
        PengaduanDokumen $dokumen
    ): BinaryFileResponse {
        $this->authorizePengaduanOwner($dokumen);

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto bukti pengaduan milik user.
     */
    public function pengaduanFoto(
        Pengaduan $pengaduan
    ): BinaryFileResponse {
        $this->authorizePengaduanFotoOwner($pengaduan);

        abort_unless(
            filled($pengaduan->foto_bukti),
            404,
            'Foto bukti tidak ditemukan.'
        );

        return $this->serveFile(
            $pengaduan->foto_bukti,
            'foto-bukti-pengaduan-' . $pengaduan->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC
    |--------------------------------------------------------------------------
    |
    | File publik tetap menggunakan private/local storage.
    |
    */

    /**
     * Menampilkan foto UMKM yang sudah disetujui
     * dan sedang aktif di publik.
     */
    public function publicUmkm(
        PengajuanUmkmFoto $foto
    ): BinaryFileResponse {
        $foto->loadMissing('pengajuan');

        $pengajuan = $foto->pengajuan;

        abort_unless(
            $pengajuan &&
            $pengajuan->status === 'disetujui' &&
            $pengajuan->is_active === true,
            404,
            'Foto UMKM tidak ditemukan.'
        );

        return $this->serveFile(
            $foto->file_path,
            'foto-umkm-' . $foto->urutan
        );
    }

    /**
     * Menampilkan thumbnail berita yang sudah dipublikasikan.
     */
    public function publicBerita(
        Berita $berita
    ): BinaryFileResponse {
        abort_unless(
            $berita->status === 'published' &&
            filled($berita->thumbnail),
            404,
            'Thumbnail berita tidak ditemukan.'
        );

        return $this->serveFile(
            $berita->thumbnail,
            'thumbnail-berita-' . $berita->id
        );
    }

    /**
     * Menampilkan foto perangkat desa yang aktif untuk publik.
     *
     * Endpoint:
     *
     * GET /api/files/public/perangkat-desa/{perangkatDesa}
     */
    public function publicPerangkatDesa(
        PerangkatDesa $perangkatDesa
    ): BinaryFileResponse {
        abort_unless(
            $perangkatDesa->is_active === true &&
            filled($perangkatDesa->foto),
            404,
            'Foto perangkat desa tidak ditemukan.'
        );

        return $this->serveFile(
            $perangkatDesa->foto,
            'perangkat-desa-' . $perangkatDesa->id
        );
    }

    /**
     * Menampilkan gambar galeri untuk publik.
     *
     * Endpoint:
     *
     * GET /api/files/public/galeri/{galeri}
     */
    public function publicGaleri(
        Galeri $galeri
    ): BinaryFileResponse {
        abort_unless(
            filled($galeri->file_path),
            404,
            'Gambar galeri tidak ditemukan.'
        );

        return $this->serveFile(
            $galeri->file_path,
            'galeri-' . $galeri->id
        );
    }

    /**
     * Menampilkan gambar hero yang aktif untuk publik.
     *
     * Endpoint:
     *
     * GET /api/files/public/hero/{heroSlide}
     */
    public function publicHero(
        HeroSlide $heroSlide
    ): BinaryFileResponse {
        abort_unless(
            $heroSlide->is_active === true &&
            filled($heroSlide->image_path),
            404,
            'Gambar hero tidak ditemukan.'
        );

        return $this->serveFile(
            $heroSlide->image_path,
            'hero-' . $heroSlide->id
        );
    }

    /**
     * Menampilkan foto Sambutan Kepala Desa untuk publik.
     *
     * Endpoint:
     *
     * GET /api/files/public/sambutan/{sambutan}
     *
     * Foto tetap berada di private/local storage.
     */
    public function publicSambutan(
        Sambutan $sambutan
    ): BinaryFileResponse {
        abort_unless(
            filled($sambutan->foto),
            404,
            'Foto sambutan tidak ditemukan.'
        );

        return $this->serveFile(
            $sambutan->foto,
            'sambutan-' . $sambutan->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN / SUPER ADMIN
    |--------------------------------------------------------------------------
    */

    /**
     * Menampilkan thumbnail berita untuk Admin / Super Admin.
     *
     * Endpoint:
     *
     * GET /api/admin/files/berita/{berita}
     */
    public function adminBerita(
        Berita $berita
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        abort_unless(
            filled($berita->thumbnail),
            404,
            'Thumbnail berita tidak ditemukan.'
        );

        return $this->serveFile(
            $berita->thumbnail,
            'thumbnail-berita-' . $berita->id
        );
    }

    /**
     * Menampilkan foto perangkat desa untuk Admin / Super Admin.
     *
     * Endpoint:
     *
     * GET /api/admin/files/perangkat-desa/{perangkatDesa}
     */
    public function adminPerangkatDesa(
        PerangkatDesa $perangkatDesa
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        abort_unless(
            filled($perangkatDesa->foto),
            404,
            'Foto perangkat desa tidak ditemukan.'
        );

        return $this->serveFile(
            $perangkatDesa->foto,
            'perangkat-desa-' . $perangkatDesa->id
        );
    }

    /**
     * Menampilkan gambar galeri untuk Admin / Super Admin.
     *
     * Endpoint:
     *
     * GET /api/admin/files/galeri/{galeri}
     */
    public function adminGaleri(
        Galeri $galeri
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        abort_unless(
            filled($galeri->file_path),
            404,
            'Gambar galeri tidak ditemukan.'
        );

        return $this->serveFile(
            $galeri->file_path,
            'galeri-' . $galeri->id
        );
    }

    /**
     * Menampilkan gambar hero untuk Admin / Super Admin.
     *
     * Endpoint:
     *
     * GET /api/admin/files/hero/{heroSlide}
     */
    public function adminHero(
        HeroSlide $heroSlide
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        abort_unless(
            filled($heroSlide->image_path),
            404,
            'Gambar hero tidak ditemukan.'
        );

        return $this->serveFile(
            $heroSlide->image_path,
            'hero-' . $heroSlide->id
        );
    }

    /**
     * Menampilkan foto sambutan kepala desa untuk Admin / Super Admin.
     *
     * Endpoint:
     *
     * GET /api/admin/files/sambutan/{sambutan}
     */
    public function adminSambutan(
        Sambutan $sambutan
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        abort_unless(
            filled($sambutan->foto),
            404,
            'Foto sambutan tidak ditemukan.'
        );

        return $this->serveFile(
            $sambutan->foto,
            'sambutan-' . $sambutan->id
        );
    }

    /**
     * Menampilkan dokumen KTP untuk Admin / Super Admin.
     */
    public function adminKtp(
        PengajuanKtpDokumen $dokumen
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan dokumen SKU untuk Admin / Super Admin.
     */
    public function adminSku(
        PengajuanSkuDokumen $dokumen
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        return $this->serveFile(
            $dokumen->file_path,
            $dokumen->nama_file
        );
    }

    /**
     * Menampilkan foto UMKM untuk Admin / Super Admin.
     */
    public function adminUmkm(
        PengajuanUmkmFoto $foto
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

        return $this->serveFile(
            $foto->file_path,
            'foto-umkm-' . $foto->urutan
        );
    }

    /**
     * Menampilkan dokumen pengaduan untuk Admin / Super Admin.
     */
    public function adminPengaduan(
        PengaduanDokumen $dokumen
    ): BinaryFileResponse {
        $this->authorizeAdminAccess();

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
        $this->authorizeAdminAccess();

        abort_unless(
            filled($pengaduan->foto_bukti),
            404,
            'Foto bukti pengaduan tidak ditemukan.'
        );

        return $this->serveFile(
            $pengaduan->foto_bukti,
            'foto-bukti-pengaduan-' . $pengaduan->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | OWNERSHIP CHECKS
    |--------------------------------------------------------------------------
    */

    /**
     * Memastikan dokumen KTP milik user login.
     */
    private function authorizeKtpOwner(
        PengajuanKtpDokumen $dokumen
    ): void {
        abort_unless(
            $dokumen
                ->pengajuan()
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );
    }

    /**
     * Memastikan dokumen SKU milik user login.
     */
    private function authorizeSkuOwner(
        PengajuanSkuDokumen $dokumen
    ): void {
        abort_unless(
            $dokumen
                ->pengajuan()
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );
    }

    /**
     * Memastikan foto UMKM milik user login.
     */
    private function authorizeUmkmOwner(
        PengajuanUmkmFoto $foto
    ): void {
        abort_unless(
            $foto
                ->pengajuan()
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->exists(),
            403,
            'Anda tidak memiliki akses ke foto ini.'
        );
    }

    /**
     * Memastikan dokumen pengaduan milik user login.
     */
    private function authorizePengaduanOwner(
        PengaduanDokumen $dokumen
    ): void {
        abort_unless(
            $dokumen
                ->pengaduan()
                ->where(
                    'user_id',
                    auth()->id()
                )
                ->exists(),
            403,
            'Anda tidak memiliki akses ke dokumen ini.'
        );
    }

    /**
     * Memastikan foto bukti pengaduan milik user login.
     */
    private function authorizePengaduanFotoOwner(
        Pengaduan $pengaduan
    ): void {
        abort_unless(
            $pengaduan->user_id === auth()->id(),
            403,
            'Anda tidak memiliki akses ke foto bukti ini.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Memastikan user memiliki role admin atau superadmin.
     *
     * Menggunakan hasRole() karena model User aplikasi
     * tidak menyediakan method hasAnyRole().
     */
    private function authorizeAdminAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user &&
            (
                $user->hasRole('admin') ||
                $user->hasRole('superadmin')
            ),
            403,
            'Anda tidak memiliki akses ke file ini.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE FILE RESPONSE
    |--------------------------------------------------------------------------
    */

    /**
     * Mengembalikan file dari private/local storage.
     *
     * MIME type dibaca langsung dari file fisik.
     */
    private function serveFile(
        string $path,
        string $filename
    ): BinaryFileResponse {
        $disk = Storage::disk('local');

        /*
        |--------------------------------------------------------------------------
        | Validasi Path
        |--------------------------------------------------------------------------
        */

        abort_unless(
            filled($path),
            404,
            'Path file tidak tersedia.'
        );

        /*
        |--------------------------------------------------------------------------
        | Cek File
        |--------------------------------------------------------------------------
        */

        abort_unless(
            $disk->exists($path),
            404,
            'File tidak ditemukan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Absolute Path
        |--------------------------------------------------------------------------
        */

        $absolutePath = $disk->path($path);

        abort_unless(
            is_file($absolutePath),
            404,
            'File tidak ditemukan.'
        );

        /*
        |--------------------------------------------------------------------------
        | MIME Type
        |--------------------------------------------------------------------------
        |
        | Jangan gunakan:
        |
        | $disk->mimeType($path)
        |
        | MIME dibaca langsung dari file fisik.
        |
        */

        $mimeType = mime_content_type($absolutePath);

        if (
            ! is_string($mimeType) ||
            $mimeType === ''
        ) {
            $mimeType = 'application/octet-stream';
        }

        /*
        |--------------------------------------------------------------------------
        | Sanitasi Filename
        |--------------------------------------------------------------------------
        */

        $safeFilename = str_replace(
            [
                "\r",
                "\n",
                '"',
            ],
            '',
            $filename
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->file(
            $absolutePath,
            [
                'Content-Type' =>
                    $mimeType,

                'Content-Disposition' =>
                    'inline; filename="' .
                    addslashes(
                        $safeFilename
                    ) .
                    '"',

                'Cache-Control' =>
                    'private, no-store, max-age=0',

                'Pragma' =>
                    'no-cache',

                'X-Content-Type-Options' =>
                    'nosniff',

                'X-Frame-Options' =>
                    'SAMEORIGIN',
            ]
        );
    }
}