<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_update_order_status_through_admin_route(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-TEST-1',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'RUB',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('admin.orders.updateStatus', $order), ['status' => Order::STATUS_PAID])
            ->assertForbidden();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_quick_checkout_rejects_invalid_quantity(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('checkout.quick', $product), ['qty' => -100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');
    }

    public function test_checkout_rejects_unknown_payment_and_delivery_methods(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 1,
                    'image' => $product->image,
                ]],
            ])
            ->postJson(route('checkout.create'), [
                'payment_method' => 'lol',
                'delivery_method' => 'free_delivery',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method', 'delivery_method']);
    }

    public function test_buyer_cannot_create_or_update_shop_profile(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Buyer shop',
                'city' => 'Test city',
                'description' => 'Should not be created',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('shops', [
            'user_id' => $buyer->id,
            'name' => 'Buyer shop',
        ]);
    }

    private function createProduct(User $seller): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'title' => 'Security test product ' . uniqid(),
            'sku' => 'TEST-' . uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Test product',
            'status' => 'draft',
        ]);
    }
}
