<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'superadmin',
                'description' => 'Tim web developer dengan akses penuh ke seluruh sistem.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'description' => 'Perangkat Desa Sumberporong yang mengelola operasional website.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'user',
                'description' => 'Masyarakat sebagai pengguna layanan website.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}