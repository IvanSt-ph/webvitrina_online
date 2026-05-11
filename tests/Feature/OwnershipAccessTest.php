<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_cannot_view_another_buyers_order(): void
    {
        $owner = User::factory()->create(['role' => 'buyer']);
        $other = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = $this->createOrder($owner, $seller);

        $this->actingAs($other)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_seller_cannot_view_another_sellers_order(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $order = $this->createOrder($buyer, $seller);

        $this->actingAs($otherSeller)
            ->get(route('seller.orders.show', $order))
            ->assertForbidden();
    }

    public function test_user_cannot_update_or_delete_another_users_cart_item(): void
    {
        $owner = User::factory()->create(['role' => 'buyer']);
        $other = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $item = CartItem::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $this->actingAs($other)
            ->patchJson(route('cart.update', $item), ['qty' => 9])
            ->assertForbidden();

        $this->assertSame(2, $item->fresh()->qty);

        $this->actingAs($other)
            ->delete(route('cart.remove', $item))
            ->assertForbidden();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_user_cannot_update_delete_or_default_another_users_address(): void
    {
        $owner = User::factory()->create(['role' => 'buyer']);
        $other = User::factory()->create(['role' => 'buyer']);

        $address = UserAddress::create([
            'user_id' => $owner->id,
            'country' => 'Moldova',
            'city' => 'Chisinau',
            'street' => 'Old street',
            'house' => '1',
            'is_default' => false,
        ]);

        $payload = [
            'country' => 'Moldova',
            'city' => 'Chisinau',
            'street' => 'Hacked street',
            'house' => '9',
        ];

        $this->actingAs($other)
            ->putJson(route('addresses.update', $address), $payload)
            ->assertForbidden();

        $this->assertSame('Old street', $address->fresh()->street);

        $this->actingAs($other)
            ->post(route('addresses.default', $address))
            ->assertForbidden();

        $this->assertFalse((bool) $address->fresh()->is_default);

        $this->actingAs($other)
            ->delete(route('addresses.destroy', $address))
            ->assertForbidden();

        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    public function test_seller_cannot_edit_update_delete_or_change_gallery_of_another_sellers_product(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($otherSeller)
            ->get(route('seller.products.edit', $product))
            ->assertForbidden();

        $this->actingAs($otherSeller)
            ->patchJson(route('seller.products.update', $product), [
                'title' => 'Changed by stranger',
            ])
            ->assertForbidden();

        $this->assertNotSame('Changed by stranger', $product->fresh()->title);

        $this->actingAs($otherSeller)
            ->deleteJson(route('seller.products.gallery.delete', $product), ['path' => 'gallery/test.jpg'])
            ->assertForbidden();

        $this->actingAs($otherSeller)
            ->delete(route('seller.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    private function createOrder(User $buyer, User $seller): Order
    {
        return Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-OWN-' . uniqid(),
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'RUB',
        ]);
    }

    private function createProduct(User $seller): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'title' => 'Ownership test product ' . uniqid(),
            'sku' => 'OWN-' . uniqid(),
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
