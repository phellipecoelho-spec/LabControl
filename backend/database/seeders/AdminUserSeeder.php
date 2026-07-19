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

        // Check if admin user already exists
        $existingUserId = DB::table('users')->where('email', 'admin@labcontrol.com')->value('id');

        if (!$existingUserId) {
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

            $existingUserId = $userId;
        }

        // Ensure the admin role is attached
        $hasRole = DB::table('role_user')
            ->where('user_id', $existingUserId)
            ->where('role_id', $adminRoleId)
            ->exists();

        if (!$hasRole) {
            DB::table('role_user')->insert([
                'role_id' => $adminRoleId,
                'user_id' => $existingUserId,
            ]);
        }
    }
}
