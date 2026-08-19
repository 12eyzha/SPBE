<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SuperAdminUserController extends Controller
{
    /**
     * Menampilkan seluruh user untuk Super Admin.
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('role')
            ->latest()
            ->paginate(20);

        return response()->json([
            'message' => 'Daftar user berhasil diambil.',
            'data' => $users,
        ]);
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
     * Menampilkan seluruh role.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('name')
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
        User $user
    ): JsonResponse {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Super Admin tidak dapat mengubah role dirinya sendiri.',
            ], 422);
        }

        $role = Role::query()
            ->where('name', $request->validated('role'))
            ->firstOrFail();

        $user->update([
            'role_id' => $role->id,
        ]);

        $user->load('role');

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
        User $user
    ): JsonResponse {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Super Admin tidak dapat menonaktifkan akun sendiri.',
            ], 422);
        }

        $user->update([
            'is_active' => $request->validated('is_active'),
        ]);

        $user->load('role');

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
}