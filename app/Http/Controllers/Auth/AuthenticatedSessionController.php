<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\UserRememberedDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public const REMEMBERED_DEVICES_COOKIE = 'wv_remembered_accounts';

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login', [
            'rememberedAccounts' => $this->rememberedAccounts(request()),
        ]);
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

        if ($request->boolean('remember')) {
            $this->rememberDevice($request);
        }

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

    public function remembered(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selector' => ['required', 'string', 'max:64'],
            'token' => ['required', 'string', 'max:128'],
        ]);

        $device = UserRememberedDevice::with('user')
            ->where('selector', $validated['selector'])
            ->where('expires_at', '>', now())
            ->first();

        if (! $device || ! hash_equals($device->token_hash, hash('sha256', $validated['token']))) {
            return back()->withErrors([
                'login' => 'Быстрый вход для этого устройства больше недоступен. Введите пароль ещё раз.',
            ]);
        }

        $device->update(['last_used_at' => now()]);

        Auth::login($device->user, true);
        $request->session()->regenerate();

        return redirect()->intended($this->fallbackRouteFor($device->user));
    }

    public function forgetRemembered(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selector' => ['required', 'string', 'max:64'],
        ]);

        UserRememberedDevice::where('selector', $validated['selector'])->delete();

        $devices = collect($this->rememberedDeviceCookie($request))
            ->reject(fn (array $device) => ($device['selector'] ?? null) === $validated['selector'])
            ->values()
            ->all();

        cookie()->queue($this->rememberedDevicesCookie(json_encode($devices)));

        return back()->with('status', 'Аккаунт убран из запомненных на этом устройстве.');
    }

    public function forgetAllRemembered(Request $request): RedirectResponse
    {
        $selectors = collect($this->rememberedDeviceCookie($request))
            ->pluck('selector')
            ->filter()
            ->all();

        if ($selectors) {
            UserRememberedDevice::whereIn('selector', $selectors)->delete();
        }

        cookie()->queue($this->rememberedDevicesCookie(json_encode([])));

        return back()->with('status', 'Все запомненные аккаунты убраны с этого устройства.');
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

    private function rememberDevice(Request $request): void
    {
        $user = $request->user();
        $selector = Str::random(32);
        $token = Str::random(64);

        UserRememberedDevice::create([
            'user_id' => $user->id,
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(60),
            'last_used_at' => now(),
        ]);

        $devices = collect($this->rememberedDeviceCookie($request))
            ->reject(fn (array $device) => ($device['selector'] ?? null) === $selector)
            ->push(['selector' => $selector, 'token' => $token])
            ->take(-5)
            ->values()
            ->all();

        cookie()->queue($this->rememberedDevicesCookie(json_encode($devices)));
    }

    private function rememberedAccounts(Request $request): array
    {
        return collect($this->rememberedDeviceCookie($request))
            ->map(function (array $cookieDevice) {
                $selector = $cookieDevice['selector'] ?? null;
                $token = $cookieDevice['token'] ?? null;

                if (! $selector || ! $token) {
                    return null;
                }

                $device = UserRememberedDevice::with('user')
                    ->where('selector', $selector)
                    ->where('expires_at', '>', now())
                    ->first();

                if (! $device || ! hash_equals($device->token_hash, hash('sha256', $token))) {
                    return null;
                }

                return [
                    'selector' => $selector,
                    'token' => $token,
                    'name' => $device->user->name,
                    'email' => $device->user->email,
                    'role' => $device->user->role,
                    'avatar_url' => $device->user->avatar_url,
                ];
            })
            ->filter()
            ->unique('selector')
            ->values()
            ->all();
    }

    private function rememberedDeviceCookie(Request $request): array
    {
        $raw = $request->cookie(self::REMEMBERED_DEVICES_COOKIE);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function rememberedDevicesCookie(string $value): Cookie
    {
        return cookie(
            self::REMEMBERED_DEVICES_COOKIE,
            $value,
            60 * 24 * 60,
            null,
            null,
            config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    private function fallbackRouteFor($user): string
    {
        return match (strtolower($user->role ?? '')) {
            'admin' => route('admin.dashboard'),
            'seller' => route('seller.products.index'),
            default => route('cabinet'),
        };
    }
}
