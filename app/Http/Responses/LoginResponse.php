<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'seller') {
            return redirect()->route('seller.products.index');
        }

        return redirect()->route('cabinet'); // обычный пользователь
    }
}
// <!-- Перенапрлениа на админ панель после логина -->