<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_search_finds_title_substrings_on_small_catalog(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $matched = $this->createProduct($seller, [
            'title' => 'Professional red drill',
            'sku' => 'DRILL-RED-1',
        ]);
        $other = $this->createProduct($seller, [
            'title' => 'Garden gloves',
            'sku' => 'GLOVES-1',
        ]);

        $this->get(route('home', ['q' => 'red']))
            ->assertOk()
            ->assertSee($matched->title)
            ->assertDontSee($other->title);
    }

    public function test_product_search_keeps_exact_sku_match_on_large_catalog(): void
    {
        Cache::put('products_total_count', 20001, 3600);

        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'No title match here',
            'sku' => 'SKU-EXACT-2026',
        ]);

        $this->get(route('home', ['q' => 'SKU-EXACT-2026']))
            ->assertOk()
            ->assertSee($product->title);
    }

    public function test_search_suggestions_return_products_categories_and_shops(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $category = Category::factory()->create([
            'name' => 'Needle Tools',
            'slug' => 'needle-tools',
        ]);
        Shop::create([
            'user_id' => $seller->id,
            'name' => 'Needle Shop',
            'slug' => 'needle-shop',
            'description' => 'Special tools store',
        ]);
        $product = $this->createProduct($seller, [
            'category_id' => $category->id,
            'title' => 'Needle product',
            'slug' => 'needle-product',
        ]);

        $this->getJson(route('search.suggest', ['q' => 'needle']))
            ->assertOk()
            ->assertJsonPath('products.0.title', $product->title)
            ->assertJsonPath('categories.0.title', 'Needle Tools')
            ->assertJsonPath('shops.0.title', 'Needle Shop')
            ->assertJsonPath('products.0.url', route('product.show', $product->slug));
    }

    private function createProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'title' => 'Search test product ' . uniqid(),
            'sku' => 'SEARCH-' . uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Search test product',
            'status' => 'active',
        ], $overrides));
    }
}
