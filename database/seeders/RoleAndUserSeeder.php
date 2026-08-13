<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superAdminRole = Role::updateOrCreate(
            ['name' => 'superadmin'],
            [
                'description' => 'Memiliki akses penuh terhadap seluruh sistem.',
            ]
        );

        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Admin Desa yang mengelola layanan dan informasi desa.',
            ]
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'user'],
            [
                'description' => 'Masyarakat yang menggunakan layanan website desa.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Akun Super Admin
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            ['email' => 'superadmin@desa.test'],
            [
                'name' => 'Super Admin',
                'role_id' => $superAdminRole->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Akun Admin Desa
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            ['email' => 'admin@desa.test'],
            [
                'name' => 'Admin Desa',
                'role_id' => $adminRole->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Akun Masyarakat
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            ['email' => 'user@desa.test'],
            [
                'name' => 'Masyarakat',
                'role_id' => $userRole->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}