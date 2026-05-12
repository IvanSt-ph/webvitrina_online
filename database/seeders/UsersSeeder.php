<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Set ADMIN_EMAIL to the real administrator email before seeding the admin user.');
        }

        if (! is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set ADMIN_PASSWORD to a unique password with at least 12 characters before seeding the admin user.');
        }

        DB::table('users')->insertOrIgnore([
            [
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
