<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
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

    public function test_single_seller_delivery_is_included_and_visible_to_every_role(): void
    {
        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100);
        $this->cart($buyer, $product);

        $this->prepare($buyer)
            ->assertSee('155,00 ₽')
            ->assertSee('255,00 ₽');

        $this->submit('courier')->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame('255.00', $order->total_price);
        $this->assertSame(100.0, $order->items_subtotal);
        $this->assertSame(155.0, $order->delivery_cost);

        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertSee('155,00 ₽');
        $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->assertSee('155,00 ₽');
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.show', $order))->assertOk()->assertSee('155,00 ₽');
    }

    public function test_multi_seller_checkout_charges_delivery_once_per_seller_order(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $firstSeller = User::factory()->create(['role' => 'seller', 'name' => 'First seller']);
        $secondSeller = User::factory()->create(['role' => 'seller', 'name' => 'Second seller']);
        $this->cart($buyer, $this->product($firstSeller, 100));
        $this->cart($buyer, $this->product($secondSeller, 100));

        $confirmation = $this->prepare($buyer);
        $confirmation
            ->assertSee('стоимость доставки начисляется отдельно для каждого продавца')
            ->assertSee('510,00 ₽');
        $this->assertSame(2, substr_count($confirmation->getContent(), 'Доставка этого заказа'));

        $this->submit('courier')->assertRedirect(route('orders.index'));

        $orders = Order::with('items')->orderBy('seller_id')->get();
        $this->assertCount(2, $orders);
        foreach ($orders as $order) {
            $this->assertSame('255.00', $order->total_price);
            $this->assertSame(155.0, $order->delivery_cost);
            $this->assertSame('courier', $order->delivery_method);
            $this->assertSame('cash', $order->payment_method);
        }
    }

    public function test_delivery_is_converted_into_non_prb_checkout_currency(): void
    {
        config(['currency.prb_per_mdl' => 2.0]);

        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100, 'PRB');
        $this->cart($buyer, $product);

        $this->prepare($buyer, 'MDL')
            ->assertSee('77,50 L')
            ->assertSee('127,50 L');

        $this->submit('courier')->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame('MDL', $order->currency);
        $this->assertSame('127.50', $order->total_price);
        $this->assertSame(77.5, $order->delivery_cost);
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

    public function test_money_failure_does_not_consume_checkout_token(): void
    {
        [$buyer, $seller] = $this->users();
        $this->cart($buyer, $this->product($seller, 1_000_000, 'PRB', 99), 99);
        $this->cart($buyer, $this->product($seller, 999_999.99), 1);
        $this->prepare($buyer);
        $token = session('checkout_token');

        $this->submit('courier')
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);

        $this->post(route('checkout.create'), [
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'checkout_token' => $token,
        ])->assertRedirect();
        $this->assertSame('99999999.99', Order::sole()->total_price);
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

        if ($deliveryMethod !== 'pickup') {
            $buyer = auth()->user();
            $payload['address_id'] = UserAddress::create([
                'user_id' => $buyer->id,
                'country' => 'MD',
                'city' => 'Tiraspol',
                'street' => 'Delivery Test Street',
                'house' => '1',
                'is_default' => true,
            ])->id;
        }

        return $this->from(route('checkout.confirm'))->post(route('checkout.create'), $payload);
    }
}
