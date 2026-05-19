<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_create_rejects_negative_price_invalid_status_and_buyer_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $payload = $this->validProductPayload(['user_id' => $buyer->id, 'price' => -10, 'status' => 'hacked']);

        $this->actingAs($admin)
            ->postJson(route('admin.products.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'price', 'status']);

        $this->assertDatabaseMissing('products', ['title' => $payload['title']]);
    }

    public function test_admin_product_create_accepts_only_seller_owner_and_active_or_draft_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $payload = $this->validProductPayload(['user_id' => $seller->id, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'title' => $payload['title'],
            'user_id' => $seller->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_product_create_rejects_svg_images(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = $this->validProductPayload([
            'image' => UploadedFile::fake()->create('product.svg', 1, 'image/svg+xml'),
            'gallery' => [
                UploadedFile::fake()->create('gallery.svg', 1, 'image/svg+xml'),
            ],
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image', 'gallery.0']);

        $this->assertDatabaseMissing('products', ['title' => $payload['title']]);
    }

    public function test_admin_product_update_rejects_invalid_status_negative_stock_and_buyer_owner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->createProduct($seller);

        $this->actingAs($admin)
            ->patchJson(route('admin.products.update', $product), [
                'user_id' => $buyer->id,
                'stock' => -1,
                'status' => 'lol',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'stock', 'status']);

        $product->refresh();
        $this->assertSame($seller->id, $product->user_id);
        $this->assertSame(10, $product->stock);
        $this->assertSame('draft', $product->status);
    }

    private function validProductPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $country = Country::create(['name' => 'Moldova']);
        $city = City::create(['country_id' => $country->id, 'name' => 'Chisinau']);
        $seller = User::factory()->create(['role' => 'seller']);

        return array_merge([
            'title' => 'Admin validation product ' . uniqid(),
            'sku' => 'ADM-' . uniqid(),
            'price' => 100,
            'stock' => 10,
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'description' => 'Valid product',
            'status' => 'draft',
        ], $overrides);
    }

    private function createProduct(User $seller): Product
    {
        $payload = $this->validProductPayload(['user_id' => $seller->id]);

        return Product::create($payload);
    }
}
