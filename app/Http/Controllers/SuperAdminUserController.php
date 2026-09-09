<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSuperAdminUserRequest;
use App\Http\Requests\UpdateSuperAdminUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserController extends Controller
{
    /**
     * ID Super Admin utama yang bersifat absolut.
     */
    private const IMMUTABLE_SUPER_ADMIN_ID = 1;

    /**
     * Menentukan apakah user adalah Super Admin utama.
     */
    private function isImmutableSuperAdmin(User $user): bool
    {
        return $user->id === self::IMMUTABLE_SUPER_ADMIN_ID;
    }

    /**
     * Menampilkan seluruh user untuk Super Admin.
     *
     * Filter:
     * - search : nama atau email
     * - role   : superadmin, admin, user
     * - status : active, inactive
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('role')
            ->when(
                $request->filled('search'),
                function ($query) use ($request) {
                    $search = $request->input('search');

                    $query->where(function ($query) use ($search) {
                        $query->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        );
                    });
                }
            )
            ->when(
                $request->filled('role'),
                function ($query) use ($request) {
                    $query->whereHas(
                        'role',
                        function ($roleQuery) use ($request) {
                            $roleQuery->where(
                                'name',
                                $request->input('role')
                            );
                        }
                    );
                }
            )
            ->when(
                $request->filled('status'),
                function ($query) use ($request) {
                    $query->where(
                        'is_active',
                        $request->input('status') === 'active'
                    );
                }
            )
            ->orderBy('id', 'asc')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'message' => 'Daftar user berhasil diambil.',
            'data' => $users,
        ]);
    }

    /**
     * Membuat user baru oleh Super Admin.
     */
    public function store(
        StoreSuperAdminUserRequest $request,
        AuditLogService $auditLogService
    ): JsonResponse {
        $validated = $request->validated();

        $role = Role::query()
            ->where('name', $validated['role'])
            ->firstOrFail();

        $isActive = $validated['is_active'] ?? true;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
            'is_active' => $isActive,
        ]);

        $user->load('role');

        $auditLogService->record(
            $request,
            'create',
            'user_management',
            "User {$user->name} dibuat dengan role {$role->name}.",
            $user,
            [],
            [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $role->id,
                'role' => $role->name,
                'is_active' => $user->is_active,
            ]
        );

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role?->id,
                    'name' => $user->role?->name,
                ],
                'is_active' => $user->is_active,
            ],
        ], 201);
    }

    /**
     * Menampilkan detail user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('role');

        return response()->json([
            'message' => 'Detail user berhasil diambil.',
            'data' => $user,
        ]);
    }

    /**
     * Mengubah data dasar user.
     *
     * Super Admin utama tidak dapat diubah.
     * Super Admin lain tetap dapat mengubah data dirinya sendiri.
     * Password bersifat opsional.
     */
    public function update(
        UpdateSuperAdminUserRequest $request,
        User $user,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Super Admin Utama
        |--------------------------------------------------------------------------
        */

        if ($this->isImmutableSuperAdmin($user)) {
            return response()->json([
                'message' => 'Data Super Admin utama tidak dapat diubah.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Data
        |--------------------------------------------------------------------------
        */

        $validated = $request->validated();

        /*
        |--------------------------------------------------------------------------
        | Nilai Lama
        |--------------------------------------------------------------------------
        */

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        /*
        |--------------------------------------------------------------------------
        | Nilai Baru
        |--------------------------------------------------------------------------
        */

        $newValues = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Update Data Dasar
        |--------------------------------------------------------------------------
        */

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        $passwordChanged = false;

        /*
        |--------------------------------------------------------------------------
        | Update Password Jika Diisi
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists('password', $validated) &&
            filled($validated['password'])
        ) {
            $user->password = Hash::make(
                $validated['password']
            );

            $passwordChanged = true;
        }

        $user->save();

        $user->load('role');

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditNewValues = $newValues;

        if ($passwordChanged) {
            $auditNewValues['password_changed'] = true;
        }

        $auditLogService->record(
            $request,
            'update',
            'user_management',
            "Data user {$user->name} diperbarui.",
            $user,
            $oldValues,
            $auditNewValues
        );

        return response()->json([
            'message' => 'Data user berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role?->id,
                    'name' => $user->role?->name,
                ],
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Menampilkan seluruh role.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Daftar role berhasil diambil.',
            'data' => $roles,
        ]);
    }

    /**
     * Mengubah role user.
     */
    public function updateRole(
        UpdateUserRoleRequest $request,
        User $user,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Super Admin Utama Tidak Boleh Diubah
        |--------------------------------------------------------------------------
        */

        if ($this->isImmutableSuperAdmin($user)) {
            return response()->json([
                'message' => 'Role Super Admin utama tidak dapat diubah.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin Tidak Boleh Mengubah Role Dirinya Sendiri
        |--------------------------------------------------------------------------
        */

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Super Admin tidak dapat mengubah role dirinya sendiri.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Role Lama
        |--------------------------------------------------------------------------
        */

        $roleIdLama = $user->role_id;

        $roleLama = Role::query()
            ->findOrFail($roleIdLama);

        /*
        |--------------------------------------------------------------------------
        | Ambil Role Baru
        |--------------------------------------------------------------------------
        */

        $roleBaru = Role::query()
            ->where('name', $request->validated('role'))
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Role Sama
        |--------------------------------------------------------------------------
        */

        if ($roleIdLama === $roleBaru->id) {
            return response()->json([
                'message' => 'User sudah memiliki role tersebut.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Role
        |--------------------------------------------------------------------------
        */

        $user->update([
            'role_id' => $roleBaru->id,
        ]);

        $user->load('role');

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'update_role',
            'user_management',
            "Role user {$user->name} diubah dari {$roleLama->name} menjadi {$roleBaru->name}.",
            $user,
            [
                'role_id' => $roleLama->id,
                'role' => $roleLama->name,
            ],
            [
                'role_id' => $roleBaru->id,
                'role' => $roleBaru->name,
            ]
        );

        return response()->json([
            'message' => 'Role user berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role?->id,
                    'name' => $user->role?->name,
                ],
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Mengaktifkan / menonaktifkan akun user.
     */
    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Super Admin Utama Tidak Boleh Diubah
        |--------------------------------------------------------------------------
        */

        if ($this->isImmutableSuperAdmin($user)) {
            return response()->json([
                'message' => 'Status Super Admin utama tidak dapat diubah.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin Tidak Boleh Mengubah Status Dirinya Sendiri
        |--------------------------------------------------------------------------
        */

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Super Admin tidak dapat menonaktifkan akun sendiri.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Status Lama
        |--------------------------------------------------------------------------
        */

        $statusLama = $user->is_active;
        $statusBaru = $request->validated('is_active');

        /*
        |--------------------------------------------------------------------------
        | Status Sama
        |--------------------------------------------------------------------------
        */

        if ($statusLama === $statusBaru) {
            return response()->json([
                'message' => $statusBaru
                    ? 'Akun user sudah aktif.'
                    : 'Akun user sudah nonaktif.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Status
        |--------------------------------------------------------------------------
        */

        $user->update([
            'is_active' => $statusBaru,
        ]);

        $user->load('role');

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'update_status',
            'user_management',
            $statusBaru
                ? "Akun user {$user->name} diaktifkan."
                : "Akun user {$user->name} dinonaktifkan.",
            $user,
            [
                'is_active' => $statusLama,
            ],
            [
                'is_active' => $statusBaru,
            ]
        );

        return response()->json([
            'message' => $user->is_active
                ? 'Akun user berhasil diaktifkan.'
                : 'Akun user berhasil dinonaktifkan.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role?->id,
                    'name' => $user->role?->name,
                ],
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Soft delete user.
     *
     * Super Admin utama tidak dapat dihapus.
     * Super Admin tidak dapat menghapus dirinya sendiri.
     */
    public function destroy(
        Request $request,
        User $user,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Super Admin Utama
        |--------------------------------------------------------------------------
        */

        if ($this->isImmutableSuperAdmin($user)) {
            return response()->json([
                'message' => 'Super Admin utama tidak dapat dihapus.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Tidak Boleh Menghapus Diri Sendiri
        |--------------------------------------------------------------------------
        */

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Super Admin tidak dapat menghapus akunnya sendiri.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Data Sebelum Soft Delete
        |--------------------------------------------------------------------------
        */

        $user->load('role');

        $oldValues = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role?->id,
            'role' => $user->role?->name,
            'is_active' => $user->is_active,
        ];

        /*
        |--------------------------------------------------------------------------
        | Soft Delete
        |--------------------------------------------------------------------------
        */

        $user->delete();

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'delete',
            'user_management',
            "User {$oldValues['name']} dihapus.",
            $user,
            $oldValues,
            [
                'deleted_at' => now()->toISOString(),
            ]
        );

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }
}