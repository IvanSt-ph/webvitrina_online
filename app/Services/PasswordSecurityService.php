<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRememberedDevice;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordSecurityService
{
    public const SESSION_KEY = 'password_security_fingerprint';

    public function fingerprint(Authenticatable $user): ?string
    {
        $password = $user->getAuthPassword();

        return is_string($password) && $password !== ''
            ? hash_hmac('sha256', $user->getAuthIdentifier().'|'.$password, config('app.key'))
            : null;
    }

    public function bindSession(Session $session, Authenticatable $user): void
    {
        // Bind the exact credentials authenticated/rotated, never a later DB version.
        $session->put(self::SESSION_KEY, $this->fingerprint($user));
    }

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
