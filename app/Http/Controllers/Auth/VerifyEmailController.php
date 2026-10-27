<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            // Authorization used an earlier snapshot. Bind the signed link to
            // the email protected by this lock before checking or writing status.
            abort_unless(hash_equals((string) $request->route('id'), (string) $user->getKey())
                && hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification())), 403);

            if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        });

        return $this->redirectAfterVerification();
    }

    /**
     * Куда редиректить после верификации email.
     */
    private function redirectAfterVerification(): RedirectResponse
    {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Если продавец → в кабинет продавца
        if (auth()->user()->isSeller()) {
            return redirect()->route('seller.cabinet');
        }

        // Если покупатель → в профиль покупателя
        return redirect()->route('buyer.profile');
    }
}
