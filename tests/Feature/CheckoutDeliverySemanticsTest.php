<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutDeliverySemanticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'currency.prb_per_mdl' => 1.0,
            'currency.prb_per_uah' => 1.0,
        ]);
    }

    public function test_single_seller_pickup_is_free_and_total_equals_subtotal(): void
    {
        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100);
        $this->cart($buyer, $product);

        $confirmation = $this->prepare($buyer);
        $confirmation
            ->assertSee('Бесплатно за каждый заказ')
            ->assertSee('Доставка этого заказа')
            ->assertSee('Итого по продавцу');

        $this->submit('pickup')->assertRedirect(route('orders.show', Order::sole()));

        $order = Order::with('items')->sole();
        $this->assertSame('100.00', $order->total_price);
        $this->assertSame(100.0, $order->items_subtotal);
        $this->assertSame(0.0, $order->delivery_cost);
    }

    public function test_courier_is_rejected_without_consuming_checkout_token(): void
    {
        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100);
        $this->cart($buyer, $product);

        $this->prepare($buyer)->assertDontSee('value="courier"', false);
        $token = session('checkout_token');
        $this->submit('courier')->assertSessionHasErrors('delivery_method');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame($token, session('checkout_token'));
        $this->submit('pickup')->assertRedirect();
        $this->assertSame(0.0, Order::sole()->delivery_cost);
    }

    public function test_multi_seller_checkout_creates_free_pickup_order_per_seller(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $firstSeller = User::factory()->create(['role' => 'seller', 'name' => 'First seller']);
        $secondSeller = User::factory()->create(['role' => 'seller', 'name' => 'Second seller']);
        $this->cart($buyer, $this->product($firstSeller, 100));
        $this->cart($buyer, $this->product($secondSeller, 100));

        $confirmation = $this->prepare($buyer);
        $confirmation
            ->assertSee('Самовывоз и выбранный способ оплаты применяются к каждому заказу')
            ->assertSee('200,00 ₽');
        $this->assertSame(2, substr_count($confirmation->getContent(), 'Доставка этого заказа'));

        $this->submit('pickup')->assertRedirect(route('orders.index'));

        $orders = Order::with('items')->orderBy('seller_id')->get();
        $this->assertCount(2, $orders);
        foreach ($orders as $order) {
            $this->assertSame('100.00', $order->total_price);
            $this->assertSame(0.0, $order->delivery_cost);
            $this->assertSame('pickup', $order->delivery_method);
            $this->assertSame('cash', $order->payment_method);
            $this->assertSame(Order::WORKFLOW_PICKUP, $order->workflow_version);
            $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        }
    }

    public function test_free_pickup_does_not_distort_non_prb_checkout_currency(): void
    {
        config(['currency.prb_per_mdl' => 2.0]);

        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100, 'PRB');
        $this->cart($buyer, $product);

        $this->prepare($buyer, 'MDL')
            ->assertSee('50,00 L')
            ->assertDontSee('77,50 L');

        $this->submit('pickup')->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame('MDL', $order->currency);
        $this->assertSame('50.00', $order->total_price);
        $this->assertSame(0.0, $order->delivery_cost);
    }

    public function test_validation_failure_does_not_consume_checkout_token(): void
    {
        [$buyer, $seller] = $this->users();
        $this->cart($buyer, $this->product($seller, 100));
        $this->prepare($buyer);
        $token = session('checkout_token');

        $this->post(route('checkout.create'), [
            'payment_method' => 'cash',
            'delivery_method' => 'unsupported',
            'checkout_token' => $token,
        ])->assertSessionHasErrors('delivery_method');

        $this->assertDatabaseCount('orders', 0);
        $this->post(route('checkout.create'), [
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'checkout_token' => $token,
        ])->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_pickup_at_money_storage_boundary_keeps_total_exact(): void
    {
        [$buyer, $seller] = $this->users();
        $this->cart($buyer, $this->product($seller, 1_000_000, 'PRB', 99), 99);
        $this->cart($buyer, $this->product($seller, 999_999.99), 1);
        $this->prepare($buyer);
        $this->submit('pickup')->assertRedirect();
        $this->assertSame('99999999.99', Order::sole()->total_price);
        $this->assertSame(0.0, Order::sole()->delivery_cost);
    }

    public function test_stock_conflict_in_multi_seller_cart_leaves_no_partial_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $first = $this->product(User::factory()->create(['role' => 'seller']), 100);
        $second = $this->product(User::factory()->create(['role' => 'seller']), 100);
        $this->cart($buyer, $first);
        $this->cart($buyer, $second);
        $this->prepare($buyer);

        $second->update(['stock' => 0]);

        $this->submit('pickup')->assertRedirect(route('cart.index'))->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $first->fresh()->stock);
    }

    private function users(): array
    {
        return [
            User::factory()->create(['role' => 'buyer']),
            User::factory()->create(['role' => 'seller']),
        ];
    }

    private function product(User $seller, float|int $price, string $currency = 'PRB', int $stock = 1): Product
    {
        $suffix = str()->lower(str()->random(10));

        return Product::create([
            'user_id' => $seller->id,
            'title' => 'Delivery semantics '.$suffix,
            'slug' => 'delivery-semantics-'.$suffix,
            'sku' => 'DELIVERY-'.strtoupper($suffix),
            'price' => $price,
            'currency_base' => $currency,
            'stock' => $stock,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    private function cart(User $buyer, Product $product, int $quantity = 1): void
    {
        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => $quantity,
        ]);
    }

    private function prepare(User $buyer, string $currency = 'PRB')
    {
        $this->actingAs($buyer)
            ->withSession(['currency' => $currency])
            ->post(route('checkout.prepare'))
            ->assertRedirect(route('checkout.confirm'));

        return $this->get(route('checkout.confirm'))->assertOk();
    }

    private function submit(string $deliveryMethod)
    {
        $payload = [
            'payment_method' => 'cash',
            'delivery_method' => $deliveryMethod,
            'checkout_token' => session('checkout_token'),
        ];

        return $this->from(route('checkout.confirm'))->post(route('checkout.create'), $payload);
    }
}
