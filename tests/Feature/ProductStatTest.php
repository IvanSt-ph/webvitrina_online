<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStat;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStatTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_counters_start_at_the_event_value_and_increment_existing_row(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $product = $this->createProduct(User::factory()->create(['role' => 'seller']));

        ProductStat::addView($product->id);

        $this->assertDatabaseHas('product_stats', [
            'product_id' => $product->id,
            'date' => today()->toDateString(),
            'views' => 1,
            'favorites' => 0,
            'carts' => 0,
        ]);

        ProductStat::addView($product->id);
        ProductStat::addFavorite($product->id);
        ProductStat::addCart($product->id);

        $this->assertDatabaseHas('product_stats', [
            'product_id' => $product->id,
            'date' => today()->toDateString(),
            'views' => 2,
            'favorites' => 1,
            'carts' => 1,
        ]);
        $this->assertSame(1, ProductStat::where('product_id', $product->id)->count());
    }

    public function test_product_view_favorite_and_cart_flows_record_their_existing_event_semantics(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $viewedProduct = $this->createProduct($seller, ['title' => 'Viewed product']);
        $favoriteProduct = $this->createProduct($seller, ['title' => 'Favorite product']);
        $cartProduct = $this->createProduct($seller, ['title' => 'Cart product']);

        $this->get(route('product.show', $viewedProduct->slug))->assertOk();
        $this->assertDatabaseHas('product_stats', [
            'product_id' => $viewedProduct->id,
            'views' => 1,
            'favorites' => 0,
            'carts' => 0,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $favoriteProduct))
            ->assertOk()
            ->assertJsonPath('favorite', true);
        $this->assertDatabaseHas('product_stats', [
            'product_id' => $favoriteProduct->id,
            'views' => 0,
            'favorites' => 1,
            'carts' => 0,
        ]);
        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $favoriteProduct))
            ->assertOk()
            ->assertJsonPath('favorite', false);
        $this->assertDatabaseHas('product_stats', [
            'product_id' => $favoriteProduct->id,
            'favorites' => 0,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $cartProduct), ['qty' => 2])
            ->assertOk();
        $this->actingAs($buyer)
            ->postJson(route('cart.add', $cartProduct), ['qty' => 1])
            ->assertOk();
        $this->assertDatabaseHas('product_stats', [
            'product_id' => $cartProduct->id,
            'views' => 0,
            'favorites' => 0,
            'carts' => 1,
        ]);
    }

    public function test_product_and_date_remain_unique(): void
    {
        $product = $this->createProduct(User::factory()->create(['role' => 'seller']));

        ProductStat::create([
            'product_id' => $product->id,
            'date' => today()->toDateString(),
        ]);

        $this->expectException(QueryException::class);

        ProductStat::create([
            'product_id' => $product->id,
            'date' => today()->toDateString(),
        ]);
    }

    private function createProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'title' => 'Product stat test '.uniqid(),
            'sku' => 'STAT-'.uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Product stat regression test',
            'status' => 'active',
        ], $overrides));
    }
}
