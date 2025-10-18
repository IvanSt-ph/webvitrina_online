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

    // ✅ Унифицируем значение роли (вдруг в БД 'Admin' с большой буквы)
    $role = strtolower($user->role ?? '');

    // 🔻 Определяем, куда вести по роли
    $fallback = match ($role) {
        'admin'  => route('admin.dashboard'),
        'seller' => route('seller.products.index'),
        default  => route('cabinet'),
    };

    // ⚙️ Если есть "intended" URL (например, пытался зайти на страницу без входа)
    // Laravel вернёт туда, иначе — в fallback
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
