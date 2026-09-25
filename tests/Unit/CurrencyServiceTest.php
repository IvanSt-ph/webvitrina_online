<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Tests\TestCase;

class CurrencyServiceTest extends TestCase
{
    use WithCachedConfig;

    public function test_rub_mdl_and_uah_are_converted_from_cached_configuration(): void
    {
        $this->assertTrue($this->app->bound('config_loaded_from_cache'));

        config()->set([
            'currency.prb_per_mdl' => 2.0,
            'currency.prb_per_uah' => 4.0,
        ]);

        $currency = new CurrencyService();

        $this->assertSame(100.0, $currency->convert(100, 'RUB', 'PRB'));
        $this->assertSame(200.0, $currency->convert(100, 'MDL', 'PRB'));
        $this->assertSame(400.0, $currency->convert(100, 'UAH', 'PRB'));
    }

    public function test_money_is_rounded_to_two_decimals_half_up(): void
    {
        config()->set([
            'currency.prb_per_mdl' => 0.335,
            'currency.prb_per_uah' => 1.0,
        ]);

        $quote = (new CurrencyService())->quote(1, 'MDL', 'PRB');

        $this->assertSame(0.34, $quote['amount']);
        $this->assertSame(0.335, $quote['rate']);
    }
}
