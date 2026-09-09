<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogBackupExport;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class SuperAdminAuditLogController extends Controller
{
    /**
     * ============================================================
     * MENAMPILKAN DAFTAR AUDIT LOG
     * ============================================================
     *
     * Mendukung:
     * - search
     * - filter module
     * - filter action
     * - filter user
     * - filter tanggal awal
     * - filter tanggal akhir
     * - pagination
     */
    public function index(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validasi Query Parameter
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'module' => [
                'nullable',
                'string',
                'max:100',
            ],

            'action' => [
                'nullable',
                'string',
                'max:100',
            ],

            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Query Dasar
        |--------------------------------------------------------------------------
        */

        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        |
        | Search pada:
        | - description
        | - module
        | - action
        | - nama user
        | - email user
        */
        if (!empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('description', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Module
        |--------------------------------------------------------------------------
        */
        if (!empty($validated['module'])) {
            $query->where(
                'module',
                $validated['module']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Action
        |--------------------------------------------------------------------------
        */
        if (!empty($validated['action'])) {
            $query->where(
                'action',
                $validated['action']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter User
        |--------------------------------------------------------------------------
        */
        if (!empty($validated['user_id'])) {
            $query->where(
                'user_id',
                $validated['user_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Tanggal Awal
        |--------------------------------------------------------------------------
        */
        if (!empty($validated['date_from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $validated['date_from']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Tanggal Akhir
        |--------------------------------------------------------------------------
        */
        if (!empty($validated['date_to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $validated['date_to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        $perPage = $validated['per_page'] ?? 30;

        $logs = $query->paginate($perPage);

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'message' => 'Audit log berhasil diambil.',
            'data' => $logs,
        ]);
    }

    /**
     * ============================================================
     * DETAIL AUDIT LOG
     * ============================================================
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Load Relasi User
        |--------------------------------------------------------------------------
        */
        $auditLog->load([
            'user:id,name,email',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
        return response()->json([
            'message' => 'Detail audit log berhasil diambil.',
            'data' => $auditLog,
        ]);
    }

    /**
     * ============================================================
     * BACKUP + HAPUS AUDIT LOG
     * ============================================================
     *
     * Alur wajib:
     *
     * 1. Validasi ID
     * 2. Ambil data audit log
     * 3. Generate XLSX
     * 4. Pastikan XLSX berhasil dibuat
     * 5. Hapus audit log
     * 6. Return file XLSX
     *
     * Kalau proses generate XLSX gagal:
     * - audit log TIDAK dihapus.
     */
    public function backupAndDelete(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validasi Request
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'ids' => [
                'required',
                'array',
                'min:1',
                'max:500',
            ],

            'ids.*' => [
                'integer',
                'distinct',
                'exists:audit_logs,id',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ambil ID
        |--------------------------------------------------------------------------
        */

        $ids = $validated['ids'];

        /*
        |--------------------------------------------------------------------------
        | Ambil Data Audit Log
        |--------------------------------------------------------------------------
        |
        | Urutkan dari data paling lama ke paling baru agar file backup
        | lebih mudah dibaca dan direkonstruksi.
        */
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->whereIn('id', $ids)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pengamanan Tambahan
        |--------------------------------------------------------------------------
        |
        | Meskipun validasi exists sudah dilakukan, kita tetap pastikan
        | seluruh data benar-benar berhasil ditemukan sebelum export.
        */
        if ($logs->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'ids' => [
                    'Sebagian audit log yang dipilih tidak ditemukan.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate XLSX SEBELUM DELETE
        |--------------------------------------------------------------------------
        |
        | Excel::raw() menghasilkan isi file terlebih dahulu.
        |
        | Ini penting karena kita tidak ingin menghapus data sebelum
        | backup benar-benar berhasil dibuat.
        */
        try {
            $contents = Excel::raw(
                new AuditLogBackupExport($logs),
                \Maatwebsite\Excel\Excel::XLSX
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Backup audit log gagal dibuat. Data tidak dihapus.',
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Pastikan File Backup Tidak Kosong
        |--------------------------------------------------------------------------
        */
        if (
            !is_string($contents) ||
            strlen($contents) === 0
        ) {
            return response()->json([
                'message' => 'Backup audit log gagal dibuat. Data tidak dihapus.',
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Hapus Data Setelah Backup Berhasil
        |--------------------------------------------------------------------------
        |
        | Delete dilakukan dalam transaction.
        */
        try {
            DB::transaction(function () use ($ids): void {
                AuditLog::query()
                    ->whereIn('id', $ids)
                    ->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Backup berhasil dibuat, tetapi penghapusan audit log gagal.',
            ], 500);
        }

        /*
        |--------------------------------------------------------------------------
        | Nama File Backup
        |--------------------------------------------------------------------------
        */

        $fileName = sprintf(
            'audit-log-backup-%s.xlsx',
            now()->format('Y-m-d-His')
        );

        /*
        |--------------------------------------------------------------------------
        | Return File XLSX
        |--------------------------------------------------------------------------
        */

        return response(
            $contents,
            200,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' =>
                    'attachment; filename="' . $fileName . '"',

                'Content-Length' =>
                    strlen($contents),

                'Cache-Control' =>
                    'no-store, no-cache, must-revalidate',

                'Pragma' =>
                    'no-cache',
            ]
        );
    }
}