<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class CurrencyProxyController extends Controller
{
public function agroprombank()
{
    try {
        $url = 'https://www.agroprombank.com/eshche/poleznoe/kursy-valyut/';

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0',
            'Accept' => 'text/html',
        ])
        ->timeout(10)
        ->get($url);

        if ($response->failed()) {
            \Log::error('Agroprombank FAILED', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return response('error loading rates', 500);
        }

        return response(
            $response->body(),
            200,
            ['Content-Type' => 'text/html; charset=utf-8']
        );

    } catch (\Throwable $e) {

        \Log::error('Agroprombank EXCEPTION', [
            'message' => $e->getMessage(),
        ]);

        return response('exception', 500);
    }
}

}
