<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRememberedDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordSecurityService
{
    public function rotate(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'password_set_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            UserRememberedDevice::where('user_id', $user->getKey())->delete();
        });
    }
}
