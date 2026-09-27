<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_mixed_currency_cart_is_saved_in_one_checkout_currency_with_source_snapshot(): void
    {
        config()->set([
            'currency.prb_per_mdl' => 2.0,
            'currency.prb_per_uah' => 4.0,
        ]);

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $rub = $this->product($seller, 'RUB', 100, 'rub');
        $mdl = $this->product($seller, 'MDL', 100, 'mdl');
        $uah = $this->product($seller, 'UAH', 100, 'uah');

        foreach ([$rub, $mdl, $uah] as $product) {
            CartItem::create([
                'user_id' => $buyer->id,
                'product_id' => $product->id,
                'qty' => 1,
            ]);
        }

        $this->actingAs($buyer)
            ->withSession(['currency' => 'MDL'])
            ->post(route('checkout.prepare'))
            ->assertRedirect(route('checkout.confirm'));

        $this->get(route('checkout.confirm'))
            ->assertOk()
            ->assertSee('50,00 L')
            ->assertSee('100,00 L')
            ->assertSee('200,00 L')
            ->assertSee('350,00 L');

        $token = session('checkout_token');

        $this->post(route('checkout.create'), [
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'checkout_token' => $token,
        ])->assertRedirect();

        $order = Order::query()->with('items')->sole();

        $this->assertSame('MDL', $order->currency);
        $this->assertSame('350.00', $order->total_price);
        $this->assertCount(3, $order->items);

        $this->assertItemSnapshot($order, $rub, '50.00', 'PRB', '0.50000000');
        $this->assertItemSnapshot($order, $mdl, '100.00', 'MDL', '1.00000000');
        $this->assertItemSnapshot($order, $uah, '200.00', 'UAH', '2.00000000');

        config()->set([
            'currency.prb_per_mdl' => 9.0,
            'currency.prb_per_uah' => 11.0,
        ]);

        $order->refresh()->load('items');
        $this->assertSame('350.00', $order->total_price);
        $this->assertSame(['50.00', '100.00', '200.00'], $order->items->sortBy('product_id')->pluck('price')->all());

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('350,00 L')
            ->assertSee('50,00 L')
            ->assertSee('100,00 L')
            ->assertSee('200,00 L');
    }

    private function product(User $seller, string $currency, float $price, string $suffix): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'title' => strtoupper($suffix).' checkout product',
            'slug' => $suffix.'-checkout-product',
            'sku' => strtoupper($suffix).'-CHECKOUT',
            'price' => $price,
            'currency_base' => $currency,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
            'description' => 'Currency checkout test',
        ]);
    }

    private function assertItemSnapshot(Order $order, Product $product, string $price, string $sourceCurrency, string $rate): void
    {
        $item = $order->items->firstWhere('product_id', $product->id);

        $this->assertNotNull($item);
        $this->assertSame($price, $item->price);
        $this->assertSame($price, $item->total);
        $this->assertSame('100.00', $item->source_price);
        $this->assertSame($sourceCurrency, $item->source_currency);
        $this->assertSame($rate, $item->exchange_rate);
    }
}
