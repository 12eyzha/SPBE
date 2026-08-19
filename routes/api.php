<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminPengaduanController;
use App\Http\Controllers\AdminPengajuanKtpController;
use App\Http\Controllers\AdminPengajuanSkuController;
use App\Http\Controllers\AdminPengajuanUmkmController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\PengajuanKtpController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\SuperAdminAuditLogController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\SuperAdminUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

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
*/

Route::middleware(['auth:sanctum', 'role:admin,superadmin'])
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
        | Pengaduan
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

Route::middleware(['auth:sanctum', 'role:superadmin'])
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

        Route::get(
            '/users/{user}',
            [SuperAdminUserController::class, 'show']
        )->name('superadmin.users.show');

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

        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/audit-logs',
            [SuperAdminAuditLogController::class, 'index']
        )->name('superadmin.audit-logs.index');

        Route::get(
            '/audit-logs/{auditLog}',
            [SuperAdminAuditLogController::class, 'show']
        )->name('superadmin.audit-logs.show');
    });