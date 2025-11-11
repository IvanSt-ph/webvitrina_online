<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function set(Request $request)
    {
        $code = strtoupper($request->input('currency', 'MDL'));
        session(['currency' => $code]);
        return back();
    }
}
