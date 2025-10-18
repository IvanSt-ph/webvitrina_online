<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RememberLocation
{
    public function handle(Request $request, Closure $next)
    {
        // 🧠 Если пришли параметры — сохраняем в сессию
        if ($request->has('country_id')) {
            session(['country_id' => $request->country_id]);
        }

        if ($request->has('city_id')) {
            session(['city_id' => $request->city_id]);
        }

        // 🧭 Если не пришли, но в сессии уже есть — подставляем в запрос
        if (!$request->has('country_id') && session()->has('country_id')) {
            $request->merge(['country_id' => session('country_id')]);
        }

        if (!$request->has('city_id') && session()->has('city_id')) {
            $request->merge(['city_id' => session('city_id')]);
        }

        // 🧹 Возможность сброса
        if ($request->has('clear_location')) {
            session()->forget(['country_id', 'city_id']);
        }

        return $next($request);
    }
}
