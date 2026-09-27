<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicCatalogValidationTest extends TestCase
{
    use RefreshDatabase;

    public static function malformedInputs(): array
    {
        return [
            'unknown sort' => [['sort' => 'unknown']],
            'column sort' => [['sort' => 'users.password', 'column' => 'password']],
            'direction' => [['sort' => 'price_asc', 'order' => 'desc; DROP TABLE products']],
            'negative pagination' => [['page' => '-2', 'per_page' => '-20']],
            'zero pagination' => [['page' => '0', 'per_page' => '0']],
            'huge pagination' => [['page' => str_repeat('9', 50), 'per_page' => str_repeat('9', 50)]],
            'text pagination' => [['page' => 'oops', 'per_page' => 'oops']],
            'arrays' => [['q' => ['bad'], 'sort' => ['bad'], 'page' => [2], 'per_page' => [2], 'category_id' => [1], 'user_id' => [1]]],
            'invalid prices' => [['min_price' => 'abc', 'max_price' => ['bad'], 'price_min' => '-1', 'price_max' => '1e999']],
            'reversed prices' => [['min_price' => '200.123', 'max_price' => '1']],
            'invalid ids' => [['category_id' => '1 OR 1=1', 'user_id' => '-1', 'country_id' => ['bad'], 'city_id' => 'NaN']],
            'unknown nested' => [['filters' => ['x' => [['bad']]], 'specifications' => [['bad']], 'brand' => [1]]],
        ];
    }

    #[DataProvider('malformedInputs')]
    public function test_catalog_normalizes_malformed_input_without_errors(array $input): void
    {
        $product = $this->product();
        $this->get(route('search', $input))->assertOk()
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 1
                && $products->perPage() === 20 && $products->pluck('id')->all() === [$product->id]);
        $this->assertSame(1, Product::count());
    }

    public function test_existing_sort_options_and_pagination_preserve_valid_filters(): void
    {
        $category = Category::factory()->create();
        $cheap = $this->product(['category_id' => $category->id, 'price' => 10]);
        $expensive = $this->product(['category_id' => $category->id, 'price' => 30]);
        foreach (['popular', 'new', 'price_asc', 'price_desc', 'rating', 'benefit'] as $sort) {
            $response = $this->get(route('search', ['sort' => $sort, 'category_id' => $category->id, 'per_page' => 1]));
            $response->assertOk()->assertViewHas('products', fn ($p) => $p->perPage() === 1 && $p->total() === 2);
            $this->assertStringContainsString('category_id='.$category->id, $response->viewData('products')->nextPageUrl());
            if (in_array($sort, ['price_asc', 'price_desc'])) {
                $this->assertSame($sort === 'price_asc' ? $cheap->id : $expensive->id, $response->viewData('products')->first()->id);
            }
        }
        $this->get(route('search', ['per_page' => 100, 'page' => 2]))->assertOk()
            ->assertViewHas('products', fn ($p) => $p->perPage() === 100 && $p->currentPage() === 2);
    }

    public function test_search_limits_and_literal_wildcards_are_preserved(): void
    {
        $literal = $this->product(['title' => 'Literal %_ token']);
        $this->product(['title' => 'Ordinary product']);
        foreach (['', ' ', str_repeat('я', 5000), "' OR 1=1 --", ['nested' => ['x']]] as $q) {
            $this->get(route('search', ['q' => $q]))->assertOk();
            $this->getJson(route('search.suggest', ['q' => $q]))->assertOk();
        }
        $this->get(route('search', ['q' => '%_']))->assertOk()
            ->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$literal->id]);
        $this->getJson(route('search.suggest', ['q' => '%_']))->assertOk()->assertJsonCount(1, 'products');
    }

    public function test_category_attribute_boundary_and_valid_filters(): void
    {
        $category = Category::factory()->create();
        $select = Attribute::create(['name' => 'Finish', 'type' => 'select', 'is_filterable' => 1, 'options' => ['Matte', 'Gloss']]);
        $number = Attribute::create(['name' => 'Size', 'type' => 'number', 'is_filterable' => 1]);
        $foreign = Attribute::create(['name' => 'Foreign', 'type' => 'select', 'is_filterable' => 1]);
        $category->attributes()->attach([$select->id, $number->id]);
        $matched = $this->product(['category_id' => $category->id]);
        $other = $this->product(['category_id' => $category->id]);
        foreach ([[$matched, 'Matte', '0'], [$other, 'Gloss', '5']] as [$product, $finish, $size]) {
            AttributeValue::create(['product_id' => $product->id, 'attribute_id' => $select->id, 'value' => $finish]);
            AttributeValue::create(['product_id' => $product->id, 'attribute_id' => $number->id, 'value' => $size]);
        }
        $this->get(route('category.show', ['slug' => $category->slug, 'filters' => [$select->id => ['Matte']]]))
            ->assertOk()->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$matched->id]);
        $this->get(route('category.show', ['slug' => $category->slug, 'filters' => [$number->id => ['from' => '0', 'to' => '0']]]))
            ->assertOk()->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$matched->id]);
        foreach (['bad', [$foreign->id => 'x'], ['1 OR 1=1' => 'x'], [$select->id => [['nested']]], [$select->id => ['from' => '1']], [$number->id => ['from' => 'oops']], [$number->id => ['from' => '10', 'to' => '1']], [$select->id => ["' OR 1=1"]]] as $filters) {
            $input = ['slug' => $category->slug, 'filters' => $filters, 'sort' => ['bad'], 'q' => ['bad'], 'page' => '-1'];
            $this->get(route('category.show', $input))->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 2);
            $this->get(route('category.ajax', $input))->assertOk();
        }
    }

    public function test_other_catalog_endpoints_and_route_parameters(): void
    {
        $product = $this->product();
        $shop = Shop::create(['user_id' => $product->user_id, 'name' => 'Boundary shop', 'slug' => 'boundary-shop']);
        foreach (['/', '/recommendations', '/seller/'.$shop->slug, '/category', '/p/'.$product->slug, '/u/'.$product->user_id] as $url) {
            $this->get($url.'?'.http_build_query(['page' => str_repeat('9', 40), 'q' => ['bad'], 'sort' => ['bad'], 'filter' => ['bad']]))->assertOk();
        }
        foreach (['/category/', '/category-ajax/', '/seller/', '/p/'] as $prefix) {
            $this->get($prefix.'missing-slug')->assertNotFound();
            $this->get($prefix.str_repeat('a', 256))->assertNotFound();
            $this->get($prefix.'1%20OR%201=1')->assertNotFound();
        }
        $this->actingAs($product->seller)->get('/products?q%5B%5D=x')->assertOk();
    }

    public function test_currency_display_survives_ignored_price_filters(): void
    {
        $product = $this->product(['price_prb' => 123, 'price_mdl' => 456, 'price_uah' => 789]);
        foreach (['PRB' => 123, 'MDL' => 456, 'UAH' => 789] as $currency => $amount) {
            $this->withSession(['currency' => $currency])
                ->get(route('search', ['user_id' => $product->user_id, 'min_price' => '9999999999999', 'max_price' => '-1']))
                ->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 1
                    && $p->first()->price_for_current_currency['amount'] === (float) $amount
                    && $p->first()->price_for_current_currency['code'] === $currency);
        }
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => User::factory()->create(['role' => 'seller'])->id,
            'title' => 'Boundary product '.uniqid(), 'sku' => 'BOUND-'.uniqid(),
            'price' => 100, 'currency_base' => 'MDL', 'price_prb' => 100,
            'price_mdl' => 100, 'price_uah' => 100, 'stock' => 10,
            'image' => 'default/no-image.png', 'description' => 'Boundary test', 'status' => 'active',
        ], $overrides));
    }
}
