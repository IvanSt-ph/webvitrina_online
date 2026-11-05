<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));
        config([
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync',
            'mail.default' => 'array', 'logging.default' => 'null',
        ]);
    }

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
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'No title match here',
            'sku' => 'SKU-EXACT-2026',
        ]);

        // The repository counts persisted products, including drafts. These rows
        // stay inside RefreshDatabase's transaction and never enter public results.
        for ($offset = 0; $offset < 20000; $offset += 500) {
            $rows = [];
            for ($index = $offset; $index < $offset + 500; $index++) {
                $rows[] = [
                    'user_id' => $seller->id,
                    'title' => 'Catalog filler '.$index,
                    'slug' => 'group16-search-filler-'.$index,
                    'sku' => 'GROUP16-FILLER-'.$index,
                    'price' => 100, 'stock' => 0, 'status' => Product::STATUS_DRAFT,
                    'currency_base' => 'MDL', 'image' => '',
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
            Product::query()->insert($rows);
        }
        $this->assertSame(20001, Product::count());

        $fullTextExecuted = false;
        DB::listen(static function (QueryExecuted $query) use (&$fullTextExecuted): void {
            if (preg_match('/match\s*\(/i', $query->sql)
                && in_array('SKU-EXACT-2026', $query->bindings, true)) {
                $fullTextExecuted = true;
            }
        });

        $this->get(route('home', ['q' => 'SKU-EXACT-2026']))
            ->assertOk()
            ->assertSee($product->title)
            ->assertViewHas('products', fn ($products) => $products->pluck('id')->all() === [$product->id]);

        if (DB::connection()->getDriverName() === 'mysql') {
            $this->assertTrue($fullTextExecuted, 'The large-catalog FULLTEXT branch must execute.');
        }
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
