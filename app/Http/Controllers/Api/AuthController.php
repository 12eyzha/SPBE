<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * Register masyarakat.
     */
    public function register(
        Request $request,
        AuditLogService $auditLogService
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                PasswordRule::defaults(),
            ],
            'password_confirmation' => [
                'required',
                'string',
                'same:password',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role Default Masyarakat
        |--------------------------------------------------------------------------
        */

        $userRole = Role::query()
            ->where('name', 'user')
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Buat User
        |--------------------------------------------------------------------------
        |
        | User model menggunakan cast:
        |
        | 'password' => 'hashed'
        |
        */

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $userRole->id,
            'is_active' => true,
        ]);

        $user->load('role');

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'create',
            'authentication',
            "Masyarakat {$user->name} berhasil melakukan registrasi.",
            $user,
            [],
            [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role?->id,
                'role' => $user->role?->name,
                'is_active' => $user->is_active,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Buat Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'api-token'
        )->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
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
     * Login semua role.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
            ],
        ]);

        $user = User::query()
            ->with('role')
            ->where('email', $credentials['email'])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Credential Check
        |--------------------------------------------------------------------------
        */

        if (
            ! $user ||
            ! Hash::check(
                $credentials['password'],
                $user->password
            )
        ) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Active Check
        |--------------------------------------------------------------------------
        */

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda sedang dinonaktifkan.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Buat Token
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'api-token'
        )->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
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
     * Mengirim link reset password.
     */
    public function forgotPassword(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Kirim Reset Link
        |--------------------------------------------------------------------------
        |
        | Untuk akun nonaktif, jangan membuat reset link.
        | Response tetap generik agar tidak membocorkan status akun/email.
        |
        */

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user && ! $user->is_active) {
            return response()->json([
                'message' => 'Jika email terdaftar, link reset password akan dikirim.',
            ]);
        }

        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Jika email terdaftar, link reset password akan dikirim.',
            'status' => $status,
        ]);
    }

    /**
     * Reset password menggunakan token.
     */
    public function resetPassword(
        Request $request,
        AuditLogService $auditLogService
    ): JsonResponse {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
            ],
            'email' => [
                'required',
                'string',
                'email',
            ],
            'password' => [
                'required',
                'string',
                PasswordRule::defaults(),
            ],
            'password_confirmation' => [
                'required',
                'string',
                'same:password',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pastikan User Ada
        |--------------------------------------------------------------------------
        */

        $targetUser = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $targetUser) {
            return response()->json([
                'message' => 'Link reset password tidak valid atau sudah kedaluwarsa.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Akun Nonaktif
        |--------------------------------------------------------------------------
        |
        | Akun nonaktif tidak boleh melakukan reset password.
        |
        */

        if (! $targetUser->is_active) {
            return response()->json([
                'message' => 'Akun Anda sedang dinonaktifkan.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Reset Password
        |--------------------------------------------------------------------------
        */

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' =>
                    $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function (
                User $user,
                string $password
            ) {
                /*
                |--------------------------------------------------------------------------
                | Update Password
                |--------------------------------------------------------------------------
                */

                $user->forceFill([
                    'password' => $password,
                    'remember_token' => null,
                ])->save();

                /*
                |--------------------------------------------------------------------------
                | Cabut Semua Token Lama
                |--------------------------------------------------------------------------
                |
                | Semua sesi API lama menjadi tidak valid.
                | User wajib login kembali menggunakan password baru.
                |
                */

                $user->tokens()->delete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Reset Gagal
        |--------------------------------------------------------------------------
        */

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil User Terbaru
        |--------------------------------------------------------------------------
        */

        $user = User::query()
            ->with('role')
            ->where('email', $validated['email'])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        |
        | Password dan token tidak pernah disimpan.
        |
        */

        if ($user) {
            $auditLogService->record(
                $request,
                'password_reset',
                'authentication',
                "Password user {$user->name} berhasil direset.",
                $user,
                [],
                [
                    'password_changed' => true,
                    'sessions_revoked' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan masuk kembali.',
        ]);
    }

    /**
     * User yang sedang login.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return response()->json([
            'user' => [
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
     * Update profile user yang sedang login.
     *
     * User dapat mengubah:
     * - name
     * - email
     *
     * Password dapat diubah secara opsional dalam request
     * yang sama jika user mengisi:
     * - current_password
     * - new_password
     * - new_password_confirmation
     */
    public function updateProfile(
        Request $request,
        AuditLogService $auditLogService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | User Saat Ini
        |--------------------------------------------------------------------------
        */

        /** @var User $user */
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Validasi Data
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],

            /*
            |--------------------------------------------------------------------------
            | Validasi Password
            |--------------------------------------------------------------------------
            |
            | Semua field password bersifat opsional.
            |
            | Tetapi jika user ingin mengganti password,
            | maka password lama, password baru, dan konfirmasi
            | password baru harus diisi.
            |
            */

            'current_password' => [
                'nullable',
                'string',
                'required_with:new_password',
            ],

            'new_password' => [
                'nullable',
                'string',
                'required_with:current_password',
                'required_with:new_password_confirmation',
                PasswordRule::defaults(),
            ],

            'new_password_confirmation' => [
                'nullable',
                'string',
                'required_with:new_password',
                'same:new_password',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Simpan Data Lama untuk Audit Log
        |--------------------------------------------------------------------------
        */

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        /*
        |--------------------------------------------------------------------------
        | Update Data Profil
        |--------------------------------------------------------------------------
        */

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        /*
        |--------------------------------------------------------------------------
        | Status Perubahan Password
        |--------------------------------------------------------------------------
        */

        $passwordChanged = false;

        /*
        |--------------------------------------------------------------------------
        | Proses Ganti Password
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['new_password'])) {
            /*
            |--------------------------------------------------------------------------
            | Cek Password Saat Ini
            |--------------------------------------------------------------------------
            */

            if (
                ! Hash::check(
                    $validated['current_password'],
                    $user->password
                )
            ) {
                return response()->json([
                    'message' => 'Password saat ini salah.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan Password Baru Berbeda
            |--------------------------------------------------------------------------
            */

            if (
                Hash::check(
                    $validated['new_password'],
                    $user->password
                )
            ) {
                return response()->json([
                    'message' => 'Password baru tidak boleh sama dengan password saat ini.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Simpan Password Baru
            |--------------------------------------------------------------------------
            |
            | User model sudah memiliki cast:
            |
            | 'password' => 'hashed'
            |
            | sehingga password akan di-hash otomatis.
            |
            */

            $user->password = $validated['new_password'];

            $passwordChanged = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan Perubahan
        |--------------------------------------------------------------------------
        */

        $user->save();

        /*
        |--------------------------------------------------------------------------
        | Load Role Terbaru
        |--------------------------------------------------------------------------
        */

        $user->load('role');

        /*
        |--------------------------------------------------------------------------
        | Data Baru untuk Audit Log
        |--------------------------------------------------------------------------
        */

        $newValues = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        /*
        |--------------------------------------------------------------------------
        | Catat Perubahan Password
        |--------------------------------------------------------------------------
        |
        | Password asli tidak pernah dimasukkan ke audit log.
        |
        */

        if ($passwordChanged) {
            $newValues['password_changed'] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        $auditLogService->record(
            $request,
            'update',
            'authentication',
            "User {$user->name} memperbarui profil.",
            $user,
            $oldValues,
            $newValues
        );

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
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
     * Logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}