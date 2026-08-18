<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat role user untuk kebutuhan test.
     */
    protected function createUserRole(): Role
    {
        return Role::create([
            'name' => 'user',
            'description' => 'Masyarakat yang menggunakan layanan website desa.',
        ]);
    }

    public function test_user_can_register(): void
    {
        $userRole = $this->createUserRole();

        $response = $this->postJson('/register', [
            'name' => 'User Test',
            'email' => 'test@desa.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Registrasi berhasil.',
                'user' => [
                    'name' => 'User Test',
                    'email' => 'test@desa.test',
                    'role' => [
                        'name' => 'user',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'User Test',
            'email' => 'test@desa.test',
            'role_id' => $userRole->id,
        ]);

        $user = User::where('email', 'test@desa.test')->firstOrFail();

        $this->assertTrue(
            Hash::check('Password123!', $user->password)
        );

        $this->assertAuthenticated();
    }

    public function test_user_can_login(): void
    {
        $role = $this->createUserRole();

        $user = User::create([
            'name' => 'User Login',
            'email' => 'login@desa.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/login', [
            'email' => 'login@desa.test',
            'password' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Login berhasil.',
                'user' => [
                    'id' => $user->id,
                    'name' => 'User Login',
                    'email' => 'login@desa.test',
                    'role' => [
                        'name' => 'user',
                    ],
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_get_current_user(): void
    {
        $role = $this->createUserRole();

        $user = User::create([
            'name' => 'Current User',
            'email' => 'current@desa.test',
            'password' => Hash::make('Password123!'),
            'role_id' => $role->id,
        ]);

        $this->actingAs($user, 'web');

        $response = $this->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Current User',
                    'email' => 'current@desa.test',
                    'role' => [
                        'name' => 'user',
                    ],
                ],
            ]);
    }

public function test_user_can_logout(): void
{
    $role = $this->createUserRole();

    $user = User::create([
        'name' => 'User Logout',
        'email' => 'logout@desa.test',
        'password' => Hash::make('Password123!'),
        'role_id' => $role->id,
    ]);

    $this->actingAs($user, 'web');

    $this->assertAuthenticated('web');

    $response = $this->postJson('/logout');

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Logout berhasil.',
        ]);

    $this->assertGuest('web');
}
}