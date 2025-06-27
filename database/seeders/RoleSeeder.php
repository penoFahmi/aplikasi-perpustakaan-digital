<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role; // <-- Pastikan Anda mengimpor model Role

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Peran dengan hak akses tertinggi
        Role::create([
            'name' => 'superadmin',
            'display_name' => 'Super Administrator',
            'level' => 100, // Level tertinggi
        ]);

        // 2. Peran admin biasa
        Role::create([
            'name' => 'pustakawan',
            'display_name' => 'Pustakawan',
            'level' => 50, // Level menengah
        ]);

        // 3. Peran untuk pengguna biasa
        Role::create([
            'name' => 'user',
            'display_name' => 'Pengguna',
            'level' => 10, // Level terendah
        ]);
    }
}
