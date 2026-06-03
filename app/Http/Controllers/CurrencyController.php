<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function set(Request $request)
    {
        $data = $request->validate([
            'currency' => ['required', 'in:PRB,RUB,MDL,UAH'],
        ]);

        $code = strtoupper($data['currency']);
        $code = $code === 'RUB' ? 'PRB' : $code;

        session(['currency' => $code]);

        if ($request->user()) {
            $request->user()->forceFill(['preferred_currency' => $code])->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'currency' => $code,
            ]);
        }

        return back();
    }
}
