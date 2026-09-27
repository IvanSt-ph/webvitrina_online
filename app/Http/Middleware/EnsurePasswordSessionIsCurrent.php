<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PasswordSecurityService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsurePasswordSessionIsCurrent
{
    public function __construct(private readonly PasswordSecurityService $security)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $guard = Auth::guard('web');

        if ($user = $guard->user()) {
            // Read authoritative credentials, including when the guard cached a user.
            $current = User::query()->useWritePdo()->find($user->getAuthIdentifier());
            $expected = $current ? $this->security->fingerprint($current) : null;
            $stored = $request->session()->get(PasswordSecurityService::SESSION_KEY);

            // Legacy/malformed sessions must reauthenticate; never enroll them here.
            if (! is_string($stored) || $expected === null || ! hash_equals($expected, $stored)) {
                $guard->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException('Unauthenticated.', ['web'], route('login'));
            }
        }

        // Do not refresh the marker on response: an in-flight request may be stale.
        return $next($request);
    }
}
