<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectAfterVerification();
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->redirectAfterVerification();
    }

    /**
     * Куда редиректить после верификации email.
     */
    private function redirectAfterVerification(): RedirectResponse
    {
        // Если продавец → в кабинет продавца
        if (auth()->user()->isSeller()) {
            return redirect()->route('seller.cabinet');
        }

        // Если покупатель → в профиль покупателя
        return redirect()->route('buyer.profile');
    }
}
