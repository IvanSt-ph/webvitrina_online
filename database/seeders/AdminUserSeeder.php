<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
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

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
                'password_set_at' => now(),
                'role' => 'admin',
            ]
        );
    }
}
