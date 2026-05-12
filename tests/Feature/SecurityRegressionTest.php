<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_seller_cannot_delete_gallery_image_from_another_product(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);

        $ownImage = 'products/gallery/own.jpg';
        $otherImage = 'products/gallery/other.jpg';

        Storage::disk('public')->put($ownImage, 'own image');
        Storage::disk('public')->put($otherImage, 'other image');

        $ownProduct = $this->createProduct($seller, ['gallery' => [$ownImage]]);
        $otherProduct = $this->createProduct($otherSeller, ['gallery' => [$otherImage]]);

        $this->actingAs($seller)
            ->deleteJson(route('seller.products.gallery.delete', $ownProduct), [
                'path' => $otherImage,
            ])
            ->assertForbidden();

        Storage::disk('public')->assertExists($otherImage);
        $this->assertSame([$otherImage], $otherProduct->fresh()->gallery);
    }

    public function test_draft_product_is_not_publicly_viewable(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'draft']);

        $this->get(route('product.show', $product->slug))
            ->assertNotFound();
    }

    public function test_draft_product_cannot_be_added_to_cart_or_quick_checkout(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'draft']);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 1])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->postJson(route('checkout.quick', $product), ['qty' => 1])
            ->assertNotFound();
    }

    public function test_seller_can_create_active_product(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $payload = $this->validSellerProductPayload([
            'status' => 'active',
        ]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $payload)
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'user_id' => $seller->id,
            'title' => $payload['title'],
            'status' => 'active',
        ]);
    }

    public function test_cart_rejects_quantity_above_product_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_checkout_rejects_cart_quantity_above_current_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 1,
            'status' => 'active',
        ]);

        $cartItem = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.prepare'), [
                'selected_items' => [$cartItem->id],
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('orders', [
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
    }

    public function test_checkout_decrements_stock_when_order_is_created(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 3,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 2,
                    'image' => $product->image,
                ]],
            ])
            ->post(route('checkout.create'), [
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
            ])
            ->assertRedirect();

        $this->assertSame(1, $product->fresh()->stock);
    }

    private function createProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
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
            'status' => 'active',
        ], $overrides));
    }

    private function validSellerProductPayload(array $overrides = []): array
    {
        $category = \App\Models\Category::factory()->create();
        $country = \App\Models\Country::create(['name' => 'Security country ' . uniqid()]);
        $city = \App\Models\City::create([
            'country_id' => $country->id,
            'name' => 'Security city ' . uniqid(),
        ]);

        return array_merge([
            'title' => 'Seller status product ' . uniqid(),
            'price' => 100,
            'stock' => 5,
            'description' => 'Valid product',
            'category_id' => $category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'status' => 'draft',
        ], $overrides);
    }
}
