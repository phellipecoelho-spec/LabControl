<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        if (!$adminRoleId) {
            return;
        }

        $userId = (string) Str::uuid();

        DB::table('users')->insert([
            'id' => $userId,
            'name' => 'Administrador',
            'email' => 'admin@labcontrol.com',
            'password' => Hash::make('@dmin123'),
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('role_user')->insert([
            'role_id' => $adminRoleId,
            'user_id' => $userId,
        ]);
    }
}
