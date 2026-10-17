<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRememberedDevice;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            $current = User::query()->whereKey($user->getKey())->lockForUpdate()->first();
            $this->assertCurrentCredentials($user, $current);

            // Revoke the old email's token before a combined email/password change.
            Password::broker()->deleteToken($current);
            $user->forceFill([
                'password' => Hash::make($password),
                'password_set_at' => now(),
                'remember_token' => Str::random(60),
            ])->save();

            UserRememberedDevice::where('user_id', $user->getKey())->delete();
            Password::broker()->deleteToken($user);
        });
    }

    public function issueRememberedDevice(User $authenticated): array
    {
        return DB::transaction(function () use ($authenticated): array {
            $current = User::query()->whereKey($authenticated->getKey())->lockForUpdate()->first();
            // Never refresh an authenticated snapshot into newer credentials.
            $this->assertCurrentCredentials($authenticated, $current);

            $selector = Str::random(32);
            $token = Str::random(64);
            UserRememberedDevice::create([
                'user_id' => $current->id,
                'selector' => $selector,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(60),
                'last_used_at' => now(),
            ]);

            return compact('selector', 'token');
        });
    }

    public function rememberedUser(string $selector, string $token): ?User
    {
        // This is only a lock locator, not authorization. Always re-read the device
        // under the user lock, in the same user -> device order used by rotation.
        $userId = UserRememberedDevice::query()->useWritePdo()->where('selector', $selector)->value('user_id');
        if ($userId === null) {
            return null;
        }

        return DB::transaction(function () use ($userId, $selector, $token): ?User {
            $user = User::query()->whereKey($userId)->lockForUpdate()->first();
            if ($user === null) {
                return null;
            }
            $device = UserRememberedDevice::where('user_id', $userId)
                ->where('selector', $selector)->lockForUpdate()->first();
            if ($device === null || $device->expires_at->lte(now())
                || ! hash_equals($device->token_hash, hash('sha256', $token))) {
                return null;
            }

            $device->update(['last_used_at' => now()]);

            // Login must bind this exact snapshot. A later rotation will invalidate
            // its session fingerprint even if it commits before the Login event.
            return $user;
        });
    }

    public function resetPassword(array $credentials, Closure $callback): string
    {
        // Include broker validation AND its final token deletion in the lock's
        // transaction, so an already validated reset cannot overtake a rotation.
        return $this->withResetUserLock($credentials, fn () => Password::reset($credentials, $callback));
    }

    public function sendResetLink(array $credentials): string
    {
        return $this->withResetUserLock($credentials, fn () => Password::sendResetLink($credentials));
    }

    private function withResetUserLock(array $credentials, Closure $operation): string
    {
        return DB::transaction(function () use ($credentials, $operation): string {
            $user = User::where('email', $credentials['email'])->lockForUpdate()->first();

            return $user === null ? Password::INVALID_USER : $operation();
        });
    }

    private function assertCurrentCredentials(User $authenticated, ?User $current): void
    {
        if ($current === null || ! hash_equals(
            (string) $authenticated->getRawOriginal('password'),
            (string) $current->getAuthPassword(),
        )) {
            throw ValidationException::withMessages([
                'login' => 'Учётные данные изменились. Войдите в аккаунт ещё раз.',
            ]);
        }
    }
}
