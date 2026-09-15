<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    private const LOGIN_ERROR = 'Не удалось войти через Google. Попробуйте снова или используйте другой способ входа.';

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return $this->failedLogin();
        }

        $providerId = $this->googleProviderId($googleUser);

        if ($providerId === null) {
            return $this->failedLogin();
        }

        $linkedUsers = User::where('provider', 'google')
            ->where('provider_id', $providerId)
            ->limit(2)
            ->get();

        if ($linkedUsers->count() > 1) {
            return $this->failedLogin();
        }

        $user = $linkedUsers->first();

        if (! $user) {
            $email = $this->verifiedGoogleEmail($googleUser);

            if ($email === null || User::where('email', $email)->exists()) {
                return $this->failedLogin();
            }

            try {
                $user = User::create([
                    'name'            => $googleUser->name ?? 'User',
                    'email'           => $email,
                    'provider'        => 'google',
                    'provider_id'     => $providerId,
                    'password'        => bcrypt(str()->random(16)),
                    'password_set_at' => null,
                    'role'            => 'buyer',
                ]);
            } catch (QueryException $e) {
                report($e);

                return $this->failedLogin();
            }
        }

        if (!$user->email_verified_at) {
            $user->sendEmailVerificationNotification();
        }

        Auth::login($user, true);

        if (!$user->email_verified_at) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('home');
    }

    private function googleProviderId(object $googleUser): ?string
    {
        $providerId = $googleUser->id ?? null;

        if (! is_string($providerId) && ! is_int($providerId)) {
            return null;
        }

        $providerId = trim((string) $providerId);

        return $providerId !== '' ? $providerId : null;
    }

    private function verifiedGoogleEmail(object $googleUser): ?string
    {
        $email = $googleUser->email ?? null;
        $raw = $googleUser->user ?? [];
        $verified = filter_var(
            $raw['email_verified'] ?? $raw['verified_email'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        if (! $verified || ! is_string($email)) {
            return null;
        }

        $email = trim($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    private function failedLogin(): RedirectResponse
    {
        return redirect()->route('login')->with('error', self::LOGIN_ERROR);
    }
}
