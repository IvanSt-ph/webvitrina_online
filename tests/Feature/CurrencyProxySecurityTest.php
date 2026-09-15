<?php

namespace Tests\Feature;

use App\Http\Controllers\CurrencyProxyController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyProxySecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_repeated_requests_do_not_amplify_upstream_calls(): void
    {
        Http::fake([
            'www.agroprombank.com/*' => Http::response(
                '<html>UAH 0.3650 / 0.4000 MDL 0.9500 / 1.0600</html>'
            ),
        ]);

        $this->getJson('/internal/currency/agroprombank')->assertOk();
        $this->getJson('/internal/currency/agroprombank')
            ->assertOk()
            ->assertHeader('X-Currency-Cache', 'fresh')
            ->assertJsonPath('rates.PRB.PRB', 1);

        Http::assertSentCount(1);
        $this->assertNotNull(Cache::get(CurrencyProxyController::FRESH_CACHE_KEY));
        $this->assertNotNull(Cache::get(CurrencyProxyController::STALE_CACHE_KEY));
    }

    public function test_expired_fresh_cache_is_refreshed_once(): void
    {
        Http::fakeSequence('www.agroprombank.com/*')
            ->push('<html>UAH 0.3650 MDL 0.9500</html>')
            ->push('<html>UAH 0.4000 MDL 1.0000</html>');

        $this->getJson('/internal/currency/agroprombank')->assertOk();
        $this->travel(CurrencyProxyController::FRESH_TTL_SECONDS + 1)->seconds();

        $this->getJson('/internal/currency/agroprombank')
            ->assertOk()
            ->assertHeader('X-Currency-Cache', 'refreshed')
            ->assertJsonPath('rates.PRB.MDL', 1);
        $this->getJson('/internal/currency/agroprombank')->assertOk();

        Http::assertSentCount(2);
    }

    public function test_upstream_request_uses_bounded_connect_and_total_timeouts(): void
    {
        $options = [];
        Http::fake(function ($request, array $requestOptions) use (&$options) {
            $options = $requestOptions;

            return Http::response('<html>UAH 0.3650 MDL 0.9500</html>');
        });

        $this->getJson('/internal/currency/agroprombank')->assertOk();

        $this->assertSame(
            CurrencyProxyController::CONNECT_TIMEOUT_SECONDS,
            $options['connect_timeout'] ?? null
        );
        $this->assertSame(
            CurrencyProxyController::TIMEOUT_SECONDS,
            $options['timeout'] ?? null
        );
    }

    public function test_upstream_connection_error_is_controlled_and_cooled_down(): void
    {
        Http::fake([
            'www.agroprombank.com/*' => Http::failedConnection('timed out'),
        ]);

        $this->getJson('/internal/currency/agroprombank')
            ->assertStatus(502)
            ->assertJson(['message' => 'currency rates temporarily unavailable']);
        $this->getJson('/internal/currency/agroprombank')->assertStatus(502);

        Http::assertSentCount(1);
        $this->assertTrue(Cache::has(CurrencyProxyController::FAILURE_CACHE_KEY));
    }

    public function test_last_known_good_is_returned_when_refresh_fails(): void
    {
        $lastKnownGood = $this->validPayload(1.05, 2.7);
        Cache::put(
            CurrencyProxyController::STALE_CACHE_KEY,
            $lastKnownGood,
            CurrencyProxyController::STALE_TTL_SECONDS
        );
        Http::fake([
            'www.agroprombank.com/*' => Http::response('unavailable', 503),
        ]);

        $this->getJson('/internal/currency/agroprombank')
            ->assertOk()
            ->assertHeader('X-Currency-Cache', 'stale')
            ->assertExactJson($lastKnownGood);

        $this->getJson('/internal/currency/agroprombank')->assertOk();
        Http::assertSentCount(1);
    }

    public function test_malformed_upstream_response_does_not_replace_last_known_good(): void
    {
        $lastKnownGood = $this->validPayload(1.05, 2.7);
        Cache::put(
            CurrencyProxyController::STALE_CACHE_KEY,
            $lastKnownGood,
            CurrencyProxyController::STALE_TTL_SECONDS
        );
        Http::fake([
            'www.agroprombank.com/*' => Http::response('<html>temporarily malformed</html>'),
        ]);

        $this->getJson('/internal/currency/agroprombank')
            ->assertOk()
            ->assertHeader('X-Currency-Cache', 'stale')
            ->assertExactJson($lastKnownGood);

        $this->assertSame(
            $lastKnownGood,
            Cache::get(CurrencyProxyController::STALE_CACHE_KEY)
        );
        $this->assertNull(Cache::get(CurrencyProxyController::FRESH_CACHE_KEY));
    }

    public function test_refresh_lock_prevents_parallel_upstream_requests(): void
    {
        $lastKnownGood = $this->validPayload(1.05, 2.7);
        Cache::put(
            CurrencyProxyController::STALE_CACHE_KEY,
            $lastKnownGood,
            CurrencyProxyController::STALE_TTL_SECONDS
        );
        Http::fake();

        $lock = Cache::lock(
            CurrencyProxyController::REFRESH_LOCK_KEY,
            CurrencyProxyController::REFRESH_LOCK_SECONDS
        );
        $this->assertTrue($lock->get());

        try {
            $this->getJson('/internal/currency/agroprombank')
                ->assertOk()
                ->assertHeader('X-Currency-Cache', 'stale')
                ->assertExactJson($lastKnownGood);

            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_currency_endpoint_rate_limit_allows_normal_flow_and_rejects_excess(): void
    {
        Http::fake([
            'www.agroprombank.com/*' => Http::response('<html>UAH 0.3650 MDL 0.9500</html>'),
        ]);

        for ($request = 1; $request <= 60; $request++) {
            $this->getJson('/internal/currency/agroprombank')->assertOk();
        }

        $this->getJson('/internal/currency/agroprombank')->assertTooManyRequests();
        Http::assertSentCount(1);
    }

    public function test_non_persistent_cache_store_fails_closed_without_upstream_request(): void
    {
        config(['cache.default' => 'null']);
        Http::fake();

        $this->getJson('/internal/currency/agroprombank')
            ->assertServiceUnavailable()
            ->assertJson(['message' => 'currency rates temporarily unavailable']);

        Http::assertNothingSent();
    }

    private function validPayload(float $mdl, float $uah): array
    {
        return [
            'rates' => [
                'PRB' => ['PRB' => 1, 'MDL' => $mdl, 'UAH' => $uah],
                'MDL' => ['PRB' => 1 / $mdl, 'MDL' => 1, 'UAH' => $uah / $mdl],
                'UAH' => ['PRB' => 1 / $uah, 'MDL' => $mdl / $uah, 'UAH' => 1],
            ],
        ];
    }
}
