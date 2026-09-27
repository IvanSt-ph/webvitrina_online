<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class CurrencyProxyController extends Controller
{
    public const FRESH_CACHE_KEY = 'currency:agroprombank:fresh';

    public const STALE_CACHE_KEY = 'currency:agroprombank:last-known-good';

    public const FAILURE_CACHE_KEY = 'currency:agroprombank:failure-cooldown';

    public const REFRESH_LOCK_KEY = 'currency:agroprombank:refresh';

    public const FRESH_TTL_SECONDS = 3600;

    public const STALE_TTL_SECONDS = 604800;

    public const FAILURE_COOLDOWN_SECONDS = 60;

    public const REFRESH_LOCK_SECONDS = 10;

    public const CONNECT_TIMEOUT_SECONDS = 2;

    public const TIMEOUT_SECONDS = 5;

    public function agroprombank(): JsonResponse
    {
        if (! $this->hasSharedCacheProtection()) {
            return $this->staleOrUnavailable(503);
        }

        if ($fresh = $this->cachedPayload(self::FRESH_CACHE_KEY)) {
            return $this->ratesResponse($fresh, 'fresh');
        }

        if (Cache::has(self::FAILURE_CACHE_KEY)) {
            return $this->staleOrUnavailable();
        }

        $lock = Cache::lock(self::REFRESH_LOCK_KEY, self::REFRESH_LOCK_SECONDS);

        if (! $lock->get()) {
            return $this->staleOrUnavailable(503);
        }

        try {
            if ($fresh = $this->cachedPayload(self::FRESH_CACHE_KEY)) {
                return $this->ratesResponse($fresh, 'fresh');
            }

            if (Cache::has(self::FAILURE_CACHE_KEY)) {
                return $this->staleOrUnavailable();
            }

            $url = 'https://www.agroprombank.com/eshche/poleznoe/kursy-valyut/';

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'text/html',
            ])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($url);

            if ($response->failed()) {
                throw new RuntimeException('Upstream returned HTTP '.$response->status());
            }

            $payload = $this->parseRates($response->body());

            Cache::put(self::FRESH_CACHE_KEY, $payload, self::FRESH_TTL_SECONDS);
            Cache::put(self::STALE_CACHE_KEY, $payload, self::STALE_TTL_SECONDS);
            Cache::forget(self::FAILURE_CACHE_KEY);

            return $this->ratesResponse($payload, 'refreshed');
        } catch (Throwable $e) {
            Cache::put(self::FAILURE_CACHE_KEY, true, self::FAILURE_COOLDOWN_SECONDS);

            Log::warning('Agroprombank rates refresh failed', [
                'message' => $e->getMessage(),
            ]);

            return $this->staleOrUnavailable();
        } finally {
            $lock->release();
        }
    }

    private function parseRates(string $html): array
    {
        $buyUAH = $this->extractBuyRate($html, 'UAH');
        $buyMDL = $this->extractBuyRate($html, 'MDL');

        $avgUAH = 1 / $buyUAH;
        $avgMDL = 1 / $buyMDL;

        $payload = [
            'rates' => [
                'PRB' => ['PRB' => 1, 'MDL' => $avgMDL, 'UAH' => $avgUAH],
                'MDL' => ['PRB' => 1 / $avgMDL, 'MDL' => 1, 'UAH' => $avgUAH / $avgMDL],
                'UAH' => ['PRB' => 1 / $avgUAH, 'MDL' => $avgMDL / $avgUAH, 'UAH' => 1],
            ],
        ];

        if (! $this->isValidPayload($payload)) {
            throw new UnexpectedValueException('Upstream rates produced an invalid matrix');
        }

        return $payload;
    }

    private function extractBuyRate(string $html, string $code): float
    {
        if (preg_match('/' . preg_quote($code, '/') . '[^0-9]+([\d.,]+)/i', $html, $match)) {
            $rate = (float) str_replace(',', '.', $match[1]);

            if ($rate > 0 && is_finite($rate)) {
                return $rate;
            }
        }

        throw new UnexpectedValueException("Missing valid {$code} buy rate");
    }

    private function cachedPayload(string $key): ?array
    {
        $payload = Cache::get($key);

        if ($payload === null) {
            return null;
        }

        if (! is_array($payload) || ! $this->isValidPayload($payload)) {
            Cache::forget($key);

            return null;
        }

        return $payload;
    }

    private function isValidPayload(array $payload): bool
    {
        foreach (['PRB', 'MDL', 'UAH'] as $from) {
            foreach (['PRB', 'MDL', 'UAH'] as $to) {
                $rate = $payload['rates'][$from][$to] ?? null;

                if (! is_numeric($rate) || (float) $rate <= 0 || ! is_finite((float) $rate)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function staleOrUnavailable(int $unavailableStatus = 502): JsonResponse
    {
        if ($stale = $this->cachedPayload(self::STALE_CACHE_KEY)) {
            return $this->ratesResponse($stale, 'stale');
        }

        return response()->json([
            'message' => 'currency rates temporarily unavailable',
        ], $unavailableStatus);
    }

    private function hasSharedCacheProtection(): bool
    {
        $store = config('cache.default');

        return $store !== 'null'
            && ($store !== 'array' || app()->environment('testing'));
    }

    private function ratesResponse(array $payload, string $cacheStatus): JsonResponse
    {
        return response()->json($payload)->header('X-Currency-Cache', $cacheStatus);
    }
}
