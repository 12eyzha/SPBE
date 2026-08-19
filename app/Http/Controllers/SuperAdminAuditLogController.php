<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuperAdminAuditLogController extends Controller
{
    /**
     * Menampilkan audit log untuk Super Admin.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(30);

        return response()->json([
            'message' => 'Audit log berhasil diambil.',
            'data' => $logs,
        ]);
    }

    /**
     * Menampilkan detail satu audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user:id,name,email');

        return response()->json([
            'message' => 'Detail audit log berhasil diambil.',
            'data' => $auditLog,
        ]);
    }
}