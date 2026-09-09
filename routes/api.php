<?php

use App\Http\Controllers\AdminBeritaController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminGaleriController;
use App\Http\Controllers\AdminHeroController;
use App\Http\Controllers\AdminKritikSaranController;
use App\Http\Controllers\AdminPengaduanController;
use App\Http\Controllers\AdminPengajuanKtpController;
use App\Http\Controllers\AdminPengajuanSkuController;
use App\Http\Controllers\AdminPengajuanUmkmController;
use App\Http\Controllers\AdminPerangkatDesaController;
use App\Http\Controllers\AdminSambutanController;
use App\Http\Controllers\AdminStatistikController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BeritaPublicController;
use App\Http\Controllers\GaleriPublicController;
use App\Http\Controllers\HeroPublicController;
use App\Http\Controllers\KategoriUmkmController;
use App\Http\Controllers\KritikSaranController;
use App\Http\Controllers\PengaduanController;
use App\Http\Controllers\PengajuanKtpController;
use App\Http\Controllers\PengajuanSkuController;
use App\Http\Controllers\PengajuanUmkmController;
use App\Http\Controllers\PerangkatDesaController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\SambutanController;
use App\Http\Controllers\StatistikDesaController;
use App\Http\Controllers\SuperAdminAuditLogController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdminUserController;
use App\Http\Controllers\UmkmPublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::post(
    '/login',
    [AuthController::class, 'login']
)->name('api.login');

Route::post(
    '/register',
    [AuthController::class, 'register']
)->name('api.register');

/*
|--------------------------------------------------------------------------
| Forgot Password
|--------------------------------------------------------------------------
*/

Route::post(
    '/forgot-password',
    [AuthController::class, 'forgotPassword']
)->name('api.password.forgot');

/*
|--------------------------------------------------------------------------
| Reset Password
|--------------------------------------------------------------------------
*/

Route::post(
    '/reset-password',
    [AuthController::class, 'resetPassword']
)->name('api.password.reset');

/*
|--------------------------------------------------------------------------
| Public UMKM
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
*/

/*
|--------------------------------------------------------------------------
| Daftar UMKM Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/umkm',
    [UmkmPublicController::class, 'index']
)->name('public.umkm.index');

/*
|--------------------------------------------------------------------------
| Detail UMKM Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/umkm/{pengajuanUmkm}',
    [UmkmPublicController::class, 'show']
)->name('public.umkm.show');

/*
|--------------------------------------------------------------------------
| Foto UMKM Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/files/public/umkm/{foto}',
    [PrivateFileController::class, 'publicUmkm']
)->name('public.files.umkm');

/*
|--------------------------------------------------------------------------
| Public Berita
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
*/

/*
|--------------------------------------------------------------------------
| Daftar Berita Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/berita',
    [BeritaPublicController::class, 'index']
)->name('public.berita.index');

/*
|--------------------------------------------------------------------------
| Detail Berita Public
|--------------------------------------------------------------------------
|
| Menggunakan slug.
|
*/

Route::get(
    '/berita/{slug}',
    [BeritaPublicController::class, 'show']
)->name('public.berita.show');

/*
|--------------------------------------------------------------------------
| Thumbnail Berita Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/files/public/berita/{berita}',
    [PrivateFileController::class, 'publicBerita']
)->name('public.files.berita');

/*
|--------------------------------------------------------------------------
| Public Sambutan Kepala Desa
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| GET /api/sambutan
|
| Data sambutan berasal dari database.
| Hanya terdapat satu sambutan aktif.
|
*/

Route::get(
    '/sambutan',
    [SambutanController::class, 'index']
)->name('public.sambutan.index');

/*
|--------------------------------------------------------------------------
| Foto Sambutan Public
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| GET /api/files/public/sambutan/{sambutan}
|
| File tetap disimpan di private/local storage.
|
*/

Route::get(
    '/files/public/sambutan/{sambutan}',
    [PrivateFileController::class, 'publicSambutan']
)->name('public.files.sambutan');

/*
|--------------------------------------------------------------------------
| Public Statistik Desa
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| GET /api/statistik
| GET /api/statistik?tahun=2026
|
| Data jumlah merupakan data asli yang dimasukkan admin.
| Persentase dihitung di frontend berdasarkan total penduduk.
|
*/

Route::get(
    '/statistik',
    [StatistikDesaController::class, 'show']
)->name('public.statistik.show');

/*
|--------------------------------------------------------------------------
| Public Perangkat Desa
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| Hanya perangkat dengan:
| is_active = true
|
| yang ditampilkan ke publik.
|
*/

/*
|--------------------------------------------------------------------------
| Daftar Perangkat Desa Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/perangkat-desa',
    [PerangkatDesaController::class, 'index']
)->name('public.perangkat-desa.index');

/*
|--------------------------------------------------------------------------
| Foto Perangkat Desa Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/files/public/perangkat-desa/{perangkatDesa}',
    [PrivateFileController::class, 'publicPerangkatDesa']
)->name('public.files.perangkat-desa');

/*
|--------------------------------------------------------------------------
| Public Galeri
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| GET /api/galeri
| GET /api/galeri/{galeri}
|
*/

/*
|--------------------------------------------------------------------------
| Daftar Galeri Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/galeri',
    [GaleriPublicController::class, 'index']
)->name('public.galeri.index');

/*
|--------------------------------------------------------------------------
| Detail Galeri Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/galeri/{galeri}',
    [GaleriPublicController::class, 'show']
)->name('public.galeri.show');

/*
|--------------------------------------------------------------------------
| Foto Galeri Public
|--------------------------------------------------------------------------
|
| File tetap berada di private/local storage.
|
*/

Route::get(
    '/files/public/galeri/{galeri}',
    [PrivateFileController::class, 'publicGaleri']
)->name('public.files.galeri');

/*
|--------------------------------------------------------------------------
| Public Hero
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| Konsep:
|
| Slide 1:
| - Welcome
| - Tidak terhubung ke berita
|
| Slide 2 dan seterusnya:
| - Berita
| - Terhubung ke berita
| - Clickable menuju detail berita
|
*/

/*
|--------------------------------------------------------------------------
| Daftar Hero Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/hero',
    [HeroPublicController::class, 'index']
)->name('public.hero.index');

/*
|--------------------------------------------------------------------------
| Detail Hero Public
|--------------------------------------------------------------------------
*/

Route::get(
    '/hero/{heroSlide}',
    [HeroPublicController::class, 'show']
)->name('public.hero.show');

/*
|--------------------------------------------------------------------------
| Gambar Hero Public
|--------------------------------------------------------------------------
|
| File tetap berada di private/local storage.
|
*/

Route::get(
    '/files/public/hero/{heroSlide}',
    [PrivateFileController::class, 'publicHero']
)->name('public.files.hero');

/*
|--------------------------------------------------------------------------
| Public Kritik & Saran
|--------------------------------------------------------------------------
|
| Tidak membutuhkan login.
|
| POST /api/kritik-saran
|
*/

Route::post(
    '/kritik-saran',
    [KritikSaranController::class, 'store']
)->name('public.kritik-saran.store');

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post(
    '/logout',
    [AuthController::class, 'logout']
)->middleware([
    'auth:sanctum',
    'active',
])->name('api.logout');

/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | User Profile
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/user',
        [AuthController::class, 'user']
    )->name('api.user');

    /*
    |--------------------------------------------------------------------------
    | Update User Profile
    |--------------------------------------------------------------------------
    |
    | User dapat memperbarui data profilnya sendiri.
    |
    */

    Route::patch(
        '/profile',
        [AuthController::class, 'updateProfile']
    )->name('api.profile.update');

    /*
    |--------------------------------------------------------------------------
    | Kategori UMKM - User
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/kategori-umkm',
        [KategoriUmkmController::class, 'index']
    )->name('kategori.umkm.index');

    /*
    |--------------------------------------------------------------------------
    | Pengajuan KTP - User
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pengajuan/ktp',
        [PengajuanKtpController::class, 'index']
    )->name('pengajuan.ktp.index');

    Route::post(
        '/pengajuan/ktp',
        [PengajuanKtpController::class, 'store']
    )->name('pengajuan.ktp.store');

    Route::get(
        '/pengajuan/ktp/{pengajuanKtp}',
        [PengajuanKtpController::class, 'show']
    )->name('pengajuan.ktp.show');

    /*
    |--------------------------------------------------------------------------
    | Pengajuan SKU - User
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pengajuan/sku',
        [PengajuanSkuController::class, 'index']
    )->name('pengajuan.sku.index');

    Route::post(
        '/pengajuan/sku',
        [PengajuanSkuController::class, 'store']
    )->name('pengajuan.sku.store');

    Route::get(
        '/pengajuan/sku/{pengajuanSku}',
        [PengajuanSkuController::class, 'show']
    )->name('pengajuan.sku.show');

    /*
    |--------------------------------------------------------------------------
    | Pengajuan UMKM - User
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pengajuan/umkm',
        [PengajuanUmkmController::class, 'index']
    )->name('pengajuan.umkm.index');

    Route::post(
        '/pengajuan/umkm',
        [PengajuanUmkmController::class, 'store']
    )->name('pengajuan.umkm.store');

    Route::get(
        '/pengajuan/umkm/{pengajuanUmkm}',
        [PengajuanUmkmController::class, 'show']
    )->name('pengajuan.umkm.show');

    Route::patch(
        '/pengajuan/umkm/{pengajuanUmkm}',
        [PengajuanUmkmController::class, 'update']
    )->name('pengajuan.umkm.update');

    Route::patch(
        '/pengajuan/umkm/{pengajuanUmkm}/active',
        [PengajuanUmkmController::class, 'toggleActive']
    )->name('pengajuan.umkm.active');

    Route::delete(
        '/pengajuan/umkm/{pengajuanUmkm}',
        [PengajuanUmkmController::class, 'destroy']
    )->name('pengajuan.umkm.destroy');

    /*
    |--------------------------------------------------------------------------
    | Pengaduan - User
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/pengaduan',
        [PengaduanController::class, 'index']
    )->name('pengaduan.index');

    Route::post(
        '/pengaduan',
        [PengaduanController::class, 'store']
    )->name('pengaduan.store');

    Route::get(
        '/pengaduan/{pengaduan}',
        [PengaduanController::class, 'show']
    )->name('pengaduan.show');

    /*
    |--------------------------------------------------------------------------
    | Private Files - User
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/files/ktp/{dokumen}',
        [PrivateFileController::class, 'ktp']
    )->name('files.ktp');

    Route::get(
        '/files/sku/{dokumen}',
        [PrivateFileController::class, 'sku']
    )->name('files.sku');

    Route::get(
        '/files/umkm/{foto}',
        [PrivateFileController::class, 'umkm']
    )->name('files.umkm');

    Route::get(
        '/files/pengaduan/{dokumen}',
        [PrivateFileController::class, 'pengaduan']
    )->name('files.pengaduan');

    Route::get(
        '/files/pengaduan/{pengaduan}/foto',
        [PrivateFileController::class, 'pengaduanFoto']
    )->name('files.pengaduan.foto');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
|
| Admin dan Super Admin dapat mengelola:
| - Dashboard
| - Sambutan
| - Hero
| - Berita
| - Statistik
| - Perangkat Desa
| - Galeri
| - Kritik & Saran
| - Pengajuan KTP
| - Pengajuan SKU
| - Pengajuan UMKM
| - Pengaduan
|
*/

Route::middleware([
    'auth:sanctum',
    'active',
    'role:admin,superadmin',
])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [AdminDashboardController::class, 'index']
        )->name('admin.dashboard');

        /*
        |--------------------------------------------------------------------------
        | Sambutan Kepala Desa
        |--------------------------------------------------------------------------
        |
        | Hanya terdapat satu sambutan aktif.
        |
        | GET    /api/admin/sambutan
        | POST   /api/admin/sambutan
        | GET    /api/admin/sambutan/{sambutan}
        | PATCH  /api/admin/sambutan/{sambutan}
        | DELETE /api/admin/sambutan/{sambutan}
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Daftar Sambutan
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/sambutan',
            [AdminSambutanController::class, 'index']
        )->name('admin.sambutan.index');

        /*
        |--------------------------------------------------------------------------
        | Tambah Sambutan
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/sambutan',
            [AdminSambutanController::class, 'store']
        )->name('admin.sambutan.store');

        /*
        |--------------------------------------------------------------------------
        | Detail Sambutan
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/sambutan/{sambutan}',
            [AdminSambutanController::class, 'show']
        )->name('admin.sambutan.show');

        /*
        |--------------------------------------------------------------------------
        | Update Sambutan
        |--------------------------------------------------------------------------
        */

        Route::patch(
            '/sambutan/{sambutan}',
            [AdminSambutanController::class, 'update']
        )->name('admin.sambutan.update');

        /*
        |--------------------------------------------------------------------------
        | Hapus Sambutan
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/sambutan/{sambutan}',
            [AdminSambutanController::class, 'destroy']
        )->name('admin.sambutan.destroy');

        /*
        |--------------------------------------------------------------------------
        | Foto Sambutan - Admin / Super Admin
        |--------------------------------------------------------------------------
        |
        | GET /api/admin/files/sambutan/{sambutan}
        |
        | Foto tetap disimpan di private/local storage.
        |
        */

        Route::get(
            '/files/sambutan/{sambutan}',
            [PrivateFileController::class, 'adminSambutan']
        )->name('admin.files.sambutan');

        /*
        |--------------------------------------------------------------------------
        | Hero
        |--------------------------------------------------------------------------
        |
        | Aturan:
        |
        | Urutan 1:
        | - Welcome
        | - berita_id = null
        |
        | Urutan 2 dan seterusnya:
        | - Berita
        | - berita_id wajib diisi
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Daftar Hero
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/hero',
            [AdminHeroController::class, 'index']
        )->name('admin.hero.index');

        /*
        |--------------------------------------------------------------------------
        | Tambah Hero
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/hero',
            [AdminHeroController::class, 'store']
        )->name('admin.hero.store');

        /*
        |--------------------------------------------------------------------------
        | Detail Hero
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/hero/{heroSlide}',
            [AdminHeroController::class, 'show']
        )->name('admin.hero.show');

        /*
        |--------------------------------------------------------------------------
        | Update Hero
        |--------------------------------------------------------------------------
        |
        | POST dipakai karena multipart/form-data
        | ketika mengganti gambar.
        |
        */

        Route::post(
            '/hero/{heroSlide}',
            [AdminHeroController::class, 'update']
        )->name('admin.hero.update');

        /*
        |--------------------------------------------------------------------------
        | Hapus Hero
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/hero/{heroSlide}',
            [AdminHeroController::class, 'destroy']
        )->name('admin.hero.destroy');

        /*
        |--------------------------------------------------------------------------
        | Gambar Hero - Admin
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/hero/{heroSlide}',
            [PrivateFileController::class, 'adminHero']
        )->name('admin.files.hero');

        /*
        |--------------------------------------------------------------------------
        | Statistik Desa
        |--------------------------------------------------------------------------
        |
        | GET  /api/admin/statistik?tahun=2026
        | POST /api/admin/statistik
        |
        */

        Route::get(
            '/statistik',
            [AdminStatistikController::class, 'index']
        )->name('admin.statistik.index');

        Route::post(
            '/statistik',
            [AdminStatistikController::class, 'store']
        )->name('admin.statistik.store');

        /*
        |--------------------------------------------------------------------------
        | Perangkat Desa
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Daftar
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/perangkat-desa',
            [AdminPerangkatDesaController::class, 'index']
        )->name('admin.perangkat-desa.index');

        /*
        |--------------------------------------------------------------------------
        | Tambah
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/perangkat-desa',
            [AdminPerangkatDesaController::class, 'store']
        )->name('admin.perangkat-desa.store');

        /*
        |--------------------------------------------------------------------------
        | Detail
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/perangkat-desa/{perangkatDesa}',
            [AdminPerangkatDesaController::class, 'show']
        )->name('admin.perangkat-desa.show');

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        |
        | POST dipakai karena multipart/form-data.
        |
        */

        Route::post(
            '/perangkat-desa/{perangkatDesa}',
            [AdminPerangkatDesaController::class, 'update']
        )->name('admin.perangkat-desa.update');

        /*
        |--------------------------------------------------------------------------
        | Hapus
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/perangkat-desa/{perangkatDesa}',
            [AdminPerangkatDesaController::class, 'destroy']
        )->name('admin.perangkat-desa.destroy');

        /*
        |--------------------------------------------------------------------------
        | Toggle Active
        |--------------------------------------------------------------------------
        */

        Route::patch(
            '/perangkat-desa/{perangkatDesa}/active',
            [AdminPerangkatDesaController::class, 'toggleActive']
        )->name('admin.perangkat-desa.active');

        /*
        |--------------------------------------------------------------------------
        | Foto Perangkat Desa - Admin
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/perangkat-desa/{perangkatDesa}',
            [PrivateFileController::class, 'adminPerangkatDesa']
        )->name('admin.files.perangkat-desa');

        /*
        |--------------------------------------------------------------------------
        | Galeri
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Daftar Galeri
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/galeri',
            [AdminGaleriController::class, 'index']
        )->name('admin.galeri.index');

        /*
        |--------------------------------------------------------------------------
        | Upload Galeri
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/galeri',
            [AdminGaleriController::class, 'store']
        )->name('admin.galeri.store');

        /*
        |--------------------------------------------------------------------------
        | Detail Galeri
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/galeri/{galeri}',
            [AdminGaleriController::class, 'show']
        )->name('admin.galeri.show');

        /*
        |--------------------------------------------------------------------------
        | Update Galeri
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/galeri/{galeri}',
            [AdminGaleriController::class, 'update']
        )->name('admin.galeri.update');

        /*
        |--------------------------------------------------------------------------
        | Hapus Galeri
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/galeri/{galeri}',
            [AdminGaleriController::class, 'destroy']
        )->name('admin.galeri.destroy');

        /*
        |--------------------------------------------------------------------------
        | Foto Galeri - Admin
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/galeri/{galeri}',
            [PrivateFileController::class, 'adminGaleri']
        )->name('admin.files.galeri');

        /*
        |--------------------------------------------------------------------------
        | Kritik & Saran
        |--------------------------------------------------------------------------
        |
        | Admin dan Super Admin dapat melihat
        | dan menghapus kritik/saran masyarakat.
        |
        */

        Route::get(
            '/kritik-saran',
            [AdminKritikSaranController::class, 'index']
        )->name('admin.kritik-saran.index');

        Route::get(
            '/kritik-saran/{kritikSaran}',
            [AdminKritikSaranController::class, 'show']
        )->name('admin.kritik-saran.show');

        Route::delete(
            '/kritik-saran/{kritikSaran}',
            [AdminKritikSaranController::class, 'destroy']
        )->name('admin.kritik-saran.destroy');

        /*
        |--------------------------------------------------------------------------
        | Berita
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/berita',
            [AdminBeritaController::class, 'index']
        )->name('admin.berita.index');

        Route::post(
            '/berita',
            [AdminBeritaController::class, 'store']
        )->name('admin.berita.store');

        Route::get(
            '/berita/{berita}',
            [AdminBeritaController::class, 'show']
        )->name('admin.berita.show');

        /*
        |--------------------------------------------------------------------------
        | Update Berita
        |--------------------------------------------------------------------------
        |
        | Menggunakan POST karena multipart/form-data
        | dengan thumbnail.
        |
        */

        Route::post(
            '/berita/{berita}',
            [AdminBeritaController::class, 'update']
        )->name('admin.berita.update');

        Route::delete(
            '/berita/{berita}',
            [AdminBeritaController::class, 'destroy']
        )->name('admin.berita.destroy');

        Route::patch(
            '/berita/{berita}/publish',
            [AdminBeritaController::class, 'publish']
        )->name('admin.berita.publish');

        Route::patch(
            '/berita/{berita}/unpublish',
            [AdminBeritaController::class, 'unpublish']
        )->name('admin.berita.unpublish');

        /*
        |--------------------------------------------------------------------------
        | Thumbnail Berita - Admin
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/berita/{berita}',
            [PrivateFileController::class, 'adminBerita']
        )->name('admin.files.berita');

        /*
        |--------------------------------------------------------------------------
        | Pengajuan KTP
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pengajuan/ktp',
            [AdminPengajuanKtpController::class, 'index']
        )->name('admin.pengajuan.ktp.index');

        Route::get(
            '/pengajuan/ktp/{pengajuanKtp}',
            [AdminPengajuanKtpController::class, 'show']
        )->name('admin.pengajuan.ktp.show');

        Route::patch(
            '/pengajuan/ktp/{pengajuanKtp}/status',
            [AdminPengajuanKtpController::class, 'updateStatus']
        )->name('admin.pengajuan.ktp.status');

        /*
        |--------------------------------------------------------------------------
        | Private Files - Admin KTP
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/ktp/{dokumen}',
            [PrivateFileController::class, 'adminKtp']
        )->name('admin.files.ktp');

        /*
        |--------------------------------------------------------------------------
        | Pengajuan SKU
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pengajuan/sku',
            [AdminPengajuanSkuController::class, 'index']
        )->name('admin.pengajuan.sku.index');

        Route::get(
            '/pengajuan/sku/{pengajuanSku}',
            [AdminPengajuanSkuController::class, 'show']
        )->name('admin.pengajuan.sku.show');

        Route::patch(
            '/pengajuan/sku/{pengajuanSku}/status',
            [AdminPengajuanSkuController::class, 'updateStatus']
        )->name('admin.pengajuan.sku.status');

        /*
        |--------------------------------------------------------------------------
        | Private Files - Admin SKU
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/sku/{dokumen}',
            [PrivateFileController::class, 'adminSku']
        )->name('admin.files.sku');

        /*
        |--------------------------------------------------------------------------
        | Pengajuan UMKM
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pengajuan/umkm',
            [AdminPengajuanUmkmController::class, 'index']
        )->name('admin.pengajuan.umkm.index');

        Route::get(
            '/pengajuan/umkm/{pengajuanUmkm}',
            [AdminPengajuanUmkmController::class, 'show']
        )->name('admin.pengajuan.umkm.show');

        Route::patch(
            '/pengajuan/umkm/{pengajuanUmkm}/status',
            [AdminPengajuanUmkmController::class, 'updateStatus']
        )->name('admin.pengajuan.umkm.status');

        /*
        |--------------------------------------------------------------------------
        | Private Files - Admin UMKM
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/umkm/{foto}',
            [PrivateFileController::class, 'adminUmkm']
        )->name('admin.files.umkm');

        /*
        |--------------------------------------------------------------------------
        | Pengaduan - Admin / Super Admin
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pengaduan',
            [AdminPengaduanController::class, 'index']
        )->name('admin.pengaduan.index');

        Route::get(
            '/pengaduan/{pengaduan}',
            [AdminPengaduanController::class, 'show']
        )->name('admin.pengaduan.show');

        Route::post(
            '/pengaduan/{pengaduan}/respon',
            [AdminPengaduanController::class, 'storeRespon']
        )->name('admin.pengaduan.respon.store');

        Route::patch(
            '/pengaduan/{pengaduan}/status',
            [AdminPengaduanController::class, 'updateStatus']
        )->name('admin.pengaduan.status');

        /*
        |--------------------------------------------------------------------------
        | Private Files - Admin Pengaduan
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/files/pengaduan/{dokumen}',
            [PrivateFileController::class, 'adminPengaduan']
        )->name('admin.files.pengaduan');

        Route::get(
            '/files/pengaduan/{pengaduan}/foto',
            [PrivateFileController::class, 'adminPengaduanFoto']
        )->name('admin.files.pengaduan.foto');
    });

/*
|--------------------------------------------------------------------------
| Super Admin
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active',
    'role:superadmin',
])
    ->prefix('superadmin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [SuperAdminDashboardController::class, 'index']
        )->name('superadmin.dashboard');

        /*
        |--------------------------------------------------------------------------
        | User Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/users',
            [SuperAdminUserController::class, 'index']
        )->name('superadmin.users.index');

        Route::post(
            '/users',
            [SuperAdminUserController::class, 'store']
        )->name('superadmin.users.store');

        Route::get(
            '/users/{user}',
            [SuperAdminUserController::class, 'show']
        )->name('superadmin.users.show');

        Route::patch(
            '/users/{user}',
            [SuperAdminUserController::class, 'update']
        )->name('superadmin.users.update');

        Route::get(
            '/roles',
            [SuperAdminUserController::class, 'roles']
        )->name('superadmin.roles.index');

        Route::patch(
            '/users/{user}/role',
            [SuperAdminUserController::class, 'updateRole']
        )->name('superadmin.users.role.update');

        Route::patch(
            '/users/{user}/status',
            [SuperAdminUserController::class, 'updateStatus']
        )->name('superadmin.users.status.update');

        Route::delete(
            '/users/{user}',
            [SuperAdminUserController::class, 'destroy']
        )->name('superadmin.users.destroy');

        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Daftar Audit Log
        |--------------------------------------------------------------------------
        |
        | Mendukung:
        | - Search
        | - Filter module
        | - Filter action
        | - Filter user
        | - Filter tanggal
        | - Pagination
        |
        */

        Route::get(
            '/audit-logs',
            [SuperAdminAuditLogController::class, 'index']
        )->name('superadmin.audit-logs.index');

        Route::post(
            '/audit-logs/backup-delete',
            [SuperAdminAuditLogController::class, 'backupAndDelete']
        )->name('superadmin.audit-logs.backup-delete');

        /*
        |--------------------------------------------------------------------------
        | Detail Audit Log
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/audit-logs/{auditLog}',
            [SuperAdminAuditLogController::class, 'show']
        )->name('superadmin.audit-logs.show');
    });