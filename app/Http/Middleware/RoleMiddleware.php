<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Проверка роли пользователя
     * $role — ожидаемая роль ('buyer', 'seller', 'admin')
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // Если пользователь не авторизован — редирект на логин
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Если роль не совпадает — 403 или редирект на главную
        if (strtolower(auth()->user()->role) !== strtolower($role)) {
            abort(403, 'Доступ запрещен'); // или redirect()->route('home');
        }

        return $next($request);
    }
}