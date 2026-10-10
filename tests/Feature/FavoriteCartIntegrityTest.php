<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteCartIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorite_toggle_and_remove_keep_rows_counters_and_stats_consistent(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $product))
            ->assertOk()
            ->assertJsonPath('favorite', true);

        $this->assertSame(1, Favorite::where('user_id', $buyer->id)->where('product_id', $product->id)->count());
        $this->assertSame(1, (int) $product->fresh()->favorites_count);
        $this->assertDatabaseHas('product_stats', ['product_id' => $product->id, 'favorites' => 1]);

        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $product))
            ->assertOk()
            ->assertJsonPath('favorite', false);

        $this->assertDatabaseMissing('favorites', ['user_id' => $buyer->id, 'product_id' => $product->id]);
        $this->assertSame(0, (int) $product->fresh()->favorites_count);
        $this->assertDatabaseHas('product_stats', ['product_id' => $product->id, 'favorites' => 0]);

        $this->actingAs($buyer)->postJson(route('favorites.toggle', $product))->assertOk();
        $favorite = Favorite::where('user_id', $buyer->id)->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($buyer)->delete(route('favorites.remove', $favorite))->assertRedirect();

        $this->assertDatabaseMissing('favorites', ['id' => $favorite->id]);
        $this->assertSame(0, (int) $product->fresh()->favorites_count);
        $this->assertDatabaseHas('product_stats', ['product_id' => $product->id, 'favorites' => 1]);
    }

    public function test_favorite_removal_flash_is_rendered_by_the_shared_toast_once(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->createProduct($seller);

        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
        $product->update(['favorites_count' => 1]);

        $response = $this->actingAs($buyer)
            ->from(route('favorites.index'))
            ->post(route('favorites.toggle', $product));

        $response
            ->assertRedirect(route('favorites.index'))
            ->assertSessionHas('success', 'Удалено из избранного');

        $page = $this->get(route('favorites.index'));

        $page->assertOk();
        $this->assertSame(1, substr_count($page->getContent(), 'flash-0'));
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
        $this->assertSame(0, (int) $product->fresh()->favorites_count);
    }

    public function test_moving_favorite_to_cart_updates_source_of_truth_once(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->createProduct($seller);
        $bulkProduct = $this->createProduct($seller);

        $this->actingAs($buyer)->postJson(route('favorites.toggle', $product))->assertOk();

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), [
                'qty' => 1,
                'remove_from_favorites' => true,
            ])
            ->assertOk()
            ->assertJsonPath('removed_from_favorites', true);

        $this->assertDatabaseMissing('favorites', ['user_id' => $buyer->id, 'product_id' => $product->id]);
        $this->assertSame(0, (int) $product->fresh()->favorites_count);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseHas('product_stats', [
            'product_id' => $product->id,
            'favorites' => 1,
            'carts' => 1,
        ]);

        $this->actingAs($buyer)->postJson(route('favorites.toggle', $bulkProduct))->assertOk();
        $bulkFavorite = Favorite::where('user_id', $buyer->id)
            ->where('product_id', $bulkProduct->id)
            ->firstOrFail();

        $this->actingAs($buyer)
            ->post(route('cart.addFavorites'), [
                'favorite_ids' => [$bulkFavorite->id],
                'remove_from_favorites' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('favorites', ['id' => $bulkFavorite->id]);
        $this->assertSame(0, (int) $bulkProduct->fresh()->favorites_count);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $bulkProduct->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseHas('product_stats', [
            'product_id' => $bulkProduct->id,
            'favorites' => 1,
            'carts' => 1,
        ]);
    }

    public function test_cart_add_update_and_remove_keep_one_row_and_one_first_add_stat(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->createProduct($seller, ['stock' => 20]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 2])
            ->assertOk()
            ->assertJsonPath('quantity', 2);
        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 3])
            ->assertOk()
            ->assertJsonPath('quantity', 5);

        $this->assertSame(1, CartItem::where('user_id', $buyer->id)->where('product_id', $product->id)->count());
        $item = CartItem::where('user_id', $buyer->id)->where('product_id', $product->id)->firstOrFail();
        $this->assertSame(5, (int) $item->qty);
        $this->assertSame(1, (int) $product->fresh()->cart_adds_count);
        $this->assertDatabaseHas('product_stats', ['product_id' => $product->id, 'carts' => 1]);

        $this->actingAs($buyer)
            ->patchJson(route('cart.update', $item), ['qty' => 4])
            ->assertOk()
            ->assertJsonPath('qty', 4);
        $this->assertSame(4, (int) $item->fresh()->qty);

        $this->actingAs($buyer)->deleteJson(route('cart.remove', $item))->assertOk();
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
        $this->assertDatabaseHas('product_stats', ['product_id' => $product->id, 'carts' => 1]);
    }

    public function test_cart_enforces_aggregate_maximum_and_current_stock_without_side_effects(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $largeStockProduct = $this->createProduct($seller, ['stock' => 1500]);
        $lowStockProduct = $this->createProduct($seller, ['stock' => 2]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $largeStockProduct), ['qty' => 999])
            ->assertOk();
        $this->actingAs($buyer)
            ->postJson(route('cart.add', $largeStockProduct), ['qty' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $largeStockProduct->id,
            'qty' => 999,
        ]);
        $this->assertDatabaseHas('product_stats', ['product_id' => $largeStockProduct->id, 'carts' => 1]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $lowStockProduct), ['qty' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $lowStockProduct->id,
        ]);
        $this->assertDatabaseMissing('product_stats', ['product_id' => $lowStockProduct->id]);
        $this->assertSame(0, (int) $lowStockProduct->fresh()->cart_adds_count);
    }

    private function createProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'title' => 'Favorite cart test '.uniqid(),
            'sku' => 'FAV-CART-'.uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Favorite and cart integrity regression test',
            'status' => 'active',
        ], $overrides));
    }
}
