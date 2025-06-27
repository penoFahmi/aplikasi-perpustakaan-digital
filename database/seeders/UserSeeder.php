<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Gunakan variabel yang berbeda untuk setiap role
        $superAdminRole = Role::where('name', 'superadmin')->first();
        $pustakawanRole = Role::where('name', 'pustakawan')->first();
        $userRole = Role::where('name', 'user')->first();

        // Buat user SuperAdmin dengan role_id yang sesuai
        User::create([
            'name' => 'SuperAdmin',
            'email' => 'superadmin@ifump.net',
            'password' => Hash::make('password'),
            'membership_date' => Carbon::now(),
            'role_id' => $superAdminRole->id,
        ]);

        // Buat user Admin dengan role_id yang sesuai
        User::create([
            'name' => 'Pustakawan',
            'email' => 'pustakawan@ifump.net', // Sesuaikan email jika perlu
            'password' => Hash::make('password'),
            'membership_date' => Carbon::now(),
            'role_id' => $pustakawanRole->id,
        ]);

        // Buat user biasa dengan role_id yang sesuai
        User::create([
            'name' => 'User',
            'email' => 'user@ifump.net',
            'password' => Hash::make('password'),
            'membership_date' => Carbon::now(),
            'role_id' => $userRole->id,
        ]);
    }
}
