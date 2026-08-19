<?php

namespace App\Http\Controllers;

use App\Models\Pengaduan;
use App\Models\PengajuanKtp;
use App\Models\PengajuanSku;
use App\Models\PengajuanUmkm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SuperAdminDashboardController extends Controller
{
    /**
     * Menampilkan statistik dashboard Super Admin.
     */
    public function index(): JsonResponse
    {
        $users = [
            'total' => User::count(),

            'aktif' => User::where(
                'is_active',
                true
            )->count(),

            'nonaktif' => User::where(
                'is_active',
                false
            )->count(),

            'admin' => User::whereHas(
                'role',
                fn ($query) => $query->where('name', 'admin')
            )->count(),

            'superadmin' => User::whereHas(
                'role',
                fn ($query) => $query->where('name', 'superadmin')
            )->count(),

            'masyarakat' => User::whereHas(
                'role',
                fn ($query) => $query->where('name', 'user')
            )->count(),
        ];

        $ktp = [
            'total' => PengajuanKtp::count(),
            'menunggu_verifikasi' => PengajuanKtp::where(
                'status',
                'menunggu_verifikasi'
            )->count(),
            'diproses' => PengajuanKtp::where(
                'status',
                'diproses'
            )->count(),
            'disetujui' => PengajuanKtp::where(
                'status',
                'disetujui'
            )->count(),
            'ditolak' => PengajuanKtp::where(
                'status',
                'ditolak'
            )->count(),
        ];

        $sku = [
            'total' => PengajuanSku::count(),
            'menunggu_verifikasi' => PengajuanSku::where(
                'status',
                'menunggu_verifikasi'
            )->count(),
            'diproses' => PengajuanSku::where(
                'status',
                'diproses'
            )->count(),
            'disetujui' => PengajuanSku::where(
                'status',
                'disetujui'
            )->count(),
            'ditolak' => PengajuanSku::where(
                'status',
                'ditolak'
            )->count(),
        ];

        $umkm = [
            'total' => PengajuanUmkm::count(),
            'menunggu_verifikasi' => PengajuanUmkm::where(
                'status',
                'menunggu_verifikasi'
            )->count(),
            'diproses' => PengajuanUmkm::where(
                'status',
                'diproses'
            )->count(),
            'disetujui' => PengajuanUmkm::where(
                'status',
                'disetujui'
            )->count(),
            'ditolak' => PengajuanUmkm::where(
                'status',
                'ditolak'
            )->count(),
            'aktif' => PengajuanUmkm::where(
                'is_active',
                true
            )->count(),
            'nonaktif' => PengajuanUmkm::where(
                'is_active',
                false
            )->count(),
        ];

        $pengaduan = [
            'total' => Pengaduan::count(),
            'terkirim' => Pengaduan::where(
                'status',
                'terkirim'
            )->count(),
            'diteruskan' => Pengaduan::where(
                'status',
                'diteruskan'
            )->count(),
            'selesai' => Pengaduan::where(
                'status',
                'selesai'
            )->count(),
        ];

        return response()->json([
            'message' => 'Dashboard Super Admin berhasil diambil.',
            'data' => [
                'users' => $users,
                'roles' => [
                    'total' => Role::count(),
                ],
                'ktp' => $ktp,
                'sku' => $sku,
                'umkm' => $umkm,
                'pengaduan' => $pengaduan,
            ],
        ]);
    }
}