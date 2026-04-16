<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        $role = strtolower($user->role ?? '');

        // 🎯 АДМИН ВСЕГДА В АДМИНКУ (игнорируем intended)
        if ($role === 'admin') {
            session()->forget('url.intended');
            return redirect()->route('admin.dashboard');
        }

        // 🔻 Для продавцов и покупателей — стандартная логика
        $fallback = match ($role) {
            'seller' => route('seller.products.index'),
            default  => route('cabinet'), // buyer
        };

        // ⚙️ Если есть "intended" URL — вернёт туда, иначе в fallback
        return redirect()->intended($fallback);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}