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
                \Log::error('Agroprombank rates request failed', [
                    'status' => $response->status(),
                ]);

                return response()->json(['message' => 'error loading rates'], 502);
            }

            return response()->json($this->parseRates($response->body()));
        } catch (\Throwable $e) {
            \Log::error('Agroprombank rates exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'exception loading rates'], 502);
        }
    }

    private function parseRates(string $html): array
    {
        $buyUAH = $this->extractBuyRate($html, 'UAH', 0.365);
        $buyMDL = $this->extractBuyRate($html, 'MDL', 0.95);

        $avgUAH = 1 / $buyUAH;
        $avgMDL = 1 / $buyMDL;

        return [
            'rates' => [
                'PRB' => ['PRB' => 1, 'MDL' => $avgMDL, 'UAH' => $avgUAH],
                'MDL' => ['PRB' => 1 / $avgMDL, 'MDL' => 1, 'UAH' => $avgUAH / $avgMDL],
                'UAH' => ['PRB' => 1 / $avgUAH, 'MDL' => $avgMDL / $avgUAH, 'UAH' => 1],
            ],
        ];
    }

    private function extractBuyRate(string $html, string $code, float $fallback): float
    {
        if (preg_match('/' . preg_quote($code, '/') . '[^0-9]+([\d.,]+)/i', $html, $match)) {
            $rate = (float) str_replace(',', '.', $match[1]);

            if ($rate > 0) {
                return $rate;
            }
        }

        return $fallback;
    }
}
