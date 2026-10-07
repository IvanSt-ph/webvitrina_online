<?php

namespace Tests\Feature;

use App\Http\Requests\ProductStoreRequest as AdminProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest as AdminProductUpdateRequest;
use App\Http\Requests\Seller\ProductStoreRequest as SellerProductStoreRequest;
use App\Http\Requests\Seller\ProductUpdateRequest as SellerProductUpdateRequest;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Support\MoneyLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MoneyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_and_admin_product_validation_enforce_existing_price_limit(): void
    {
        foreach ([
            SellerProductStoreRequest::class,
            SellerProductUpdateRequest::class,
            AdminProductStoreRequest::class,
            AdminProductUpdateRequest::class,
        ] as $requestClass) {
            $rules = (new $requestClass)->rules();

            $this->assertFalse(
                Validator::make(['price' => MoneyLimits::PRODUCT_PRICE_MAX], $rules)->errors()->has('price'),
                $requestClass.' rejected the maximum valid product price.'
            );
            $this->assertTrue(
                Validator::make(['price' => MoneyLimits::PRODUCT_PRICE_MAX + 0.01], $rules)->errors()->has('price'),
                $requestClass.' accepted a product price above the business limit.'
            );
        }
    }

    public function test_maximum_product_price_and_maximum_cart_quantity_can_checkout_safely(): void
    {
        [$buyer, $seller] = $this->users();
        $maximumPrice = $this->product($seller, MoneyLimits::PRODUCT_PRICE_MAX, 1);
        $maximumQuantity = $this->product($seller, 1, 999);

        $this->addToCart($buyer, $maximumPrice, 1);
        $this->addToCart($buyer, $maximumQuantity, 999);

        $this->checkout($buyer)->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame('1000999.00', $order->total_price);
        $this->assertSame(['1000000.00', '999.00'], $order->items->pluck('total')->all());
    }

    public function test_line_total_just_below_decimal_boundary_is_persisted(): void
    {
        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100100.10, 999);
        $this->addToCart($buyer, $product, 999);

        $this->checkout($buyer)->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame('99999999.90', $order->total_price);
        $this->assertSame('99999999.90', $order->items->sole()->total);
    }

    public function test_line_total_above_decimal_boundary_is_rejected_without_sql_error(): void
    {
        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 100100.11, 999);
        $this->addToCart($buyer, $product, 999);

        $this->checkout($buyer)
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(999, $product->fresh()->stock);
    }

    public function test_multiple_items_can_reach_exact_order_total_boundary(): void
    {
        [$buyer, $seller] = $this->users();
        $first = $this->product($seller, 1_000_000, 99);
        $second = $this->product($seller, 999_999.99, 1);
        $this->addToCart($buyer, $first, 99);
        $this->addToCart($buyer, $second, 1);

        $this->checkout($buyer)->assertRedirect();

        $this->assertSame('99999999.99', Order::sole()->total_price);
    }

    public function test_multiple_items_above_order_total_boundary_are_rejected(): void
    {
        [$buyer, $seller] = $this->users();
        $first = $this->product($seller, 1_000_000, 99);
        $second = $this->product($seller, 1_000_000, 1);
        $this->addToCart($buyer, $first, 99);
        $this->addToCart($buyer, $second, 1);

        $this->checkout($buyer)
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_delivery_cannot_push_order_total_past_storage_boundary(): void
    {
        [$buyer, $seller] = $this->users();
        $first = $this->product($seller, 1_000_000, 99);
        $second = $this->product($seller, 999_999.99, 1);
        $this->addToCart($buyer, $first, 99);
        $this->addToCart($buyer, $second, 1);

        $this->checkout($buyer, 'courier')
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_currency_conversion_is_included_in_overflow_guard(): void
    {
        config(['currency.prb_per_mdl' => 2.0]);

        [$buyer, $seller] = $this->users();
        $product = $this->product($seller, 1_000_000, 50, 'MDL');
        $this->addToCart($buyer, $product, 50);

        $this->checkout($buyer)
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
    }

    private function users(): array
    {
        return [
            User::factory()->create(['role' => 'buyer']),
            User::factory()->create(['role' => 'seller']),
        ];
    }

    private function product(User $seller, float|int $price, int $stock, string $currency = 'PRB'): Product
    {
        $suffix = str()->lower(str()->random(10));

        return Product::create([
            'user_id' => $seller->id,
            'title' => 'Money boundary product '.$suffix,
            'slug' => 'money-boundary-'.$suffix,
            'sku' => 'MONEY-'.strtoupper($suffix),
            'price' => $price,
            'currency_base' => $currency,
            'stock' => $stock,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    private function addToCart(User $buyer, Product $product, int $quantity): void
    {
        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => $quantity,
        ]);
    }

    private function checkout(User $buyer, string $deliveryMethod = 'pickup'): TestResponse
    {
        $this->actingAs($buyer)
            ->withSession(['currency' => 'PRB'])
            ->post(route('checkout.prepare'))
            ->assertRedirect(route('checkout.confirm'));

        $this->get(route('checkout.confirm'))->assertOk();

        $addressId = null;
        if ($deliveryMethod !== 'pickup') {
            $addressId = UserAddress::create([
                'user_id' => $buyer->id,
                'country' => 'MD',
                'city' => 'Tiraspol',
                'street' => 'Money Test Street',
                'house' => '1',
                'is_default' => true,
            ])->id;
        }

        return $this->from(route('checkout.confirm'))->post(route('checkout.create'), [
            'payment_method' => 'cash',
            'delivery_method' => $deliveryMethod,
            'address_id' => $addressId,
            'checkout_token' => session('checkout_token'),
        ]);
    }
}
