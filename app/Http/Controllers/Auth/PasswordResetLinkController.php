<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    public function __construct(private readonly PasswordSecurityService $passwordSecurity)
    {
    }

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->passwordSecurity->sendResetLink(
                $request->only('email')
            );
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Password reset email delivery failed.', [
                'exception' => $exception::class,
            ]);
        }

        return back()->with('status', 'Если аккаунт с таким адресом существует, мы отправим инструкции по восстановлению пароля');
    }
}
