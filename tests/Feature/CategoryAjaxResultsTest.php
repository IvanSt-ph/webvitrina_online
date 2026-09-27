<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryAjaxResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_nonzero_zero_nonzero_and_plain_pages_keep_results_contract(): void
    {
        $category = Category::factory()->create();
        $attribute = Attribute::create(['name' => 'Finish', 'type' => 'select', 'is_filterable' => 1, 'options' => ['Matte', 'Gloss']]);
        $category->attributes()->attach($attribute);
        $seller = User::factory()->create(['role' => 'seller']);
        for ($i = 0; $i < 21; $i++) {
            $product = Product::create([
                'user_id' => $seller->id, 'category_id' => $category->id,
                'title' => "PROD11 card $i", 'price' => 100, 'stock' => 5, 'status' => 'active',
            ]);
            AttributeValue::create(['product_id' => $product->id, 'attribute_id' => $attribute->id, 'value' => 'Matte']);
        }

        foreach ([true, false] as $ajax) {
            foreach (['Matte', 'Gloss', 'Matte'] as $finish) {
                $url = route('category.show', ['slug' => $category->slug, 'sort' => 'price_asc', 'filters' => [$attribute->id => [$finish]]]);
                $response = $this->get($url, $ajax ? ['X-Requested-With' => 'XMLHttpRequest'] : []);
                $response->assertOk()->assertViewHas('activeFilters', [$attribute->id => [$finish]]);
                $dom = new \DOMDocument();
                @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
                $xpath = new \DOMXPath($dom);
                $containers = $xpath->query('//*[@id="products-container"]');
                $this->assertSame(1, $containers->length, "$finish must have one results container");
                $container = $containers->item(0);
                $cards = $xpath->query('.//*[@data-load-more-item]', $container);
                $controls = $xpath->query('.//*[@data-load-more-controls]', $container);
                if ($finish === 'Gloss') {
                    $this->assertSame(0, $cards->length);
                    $this->assertSame(0, $controls->length);
                    $this->assertStringContainsString('В этой категории пока нет товаров.', $container->textContent);
                    $this->assertStringNotContainsString('PROD11 card', $container->textContent);
                    $this->assertSame(0, $response->viewData('products')->total());
                } else {
                    $this->assertSame(20, $cards->length);
                    $this->assertSame(1, $controls->length);
                    parse_str(parse_url($response->viewData('products')->nextPageUrl(), PHP_URL_QUERY), $query);
                    $this->assertSame([$attribute->id => ['Matte']], $query['filters']);
                    $this->assertSame('price_asc', $query['sort']);
                    $this->assertSame('2', $query['page']);
                }
                // The category's existing JS replaces the complete contents, including pagination.
                $response->assertSee('productsContainer.innerHTML = newProducts.innerHTML;', false)
                    ->assertSee("window.history.replaceState({}, '', url);", false);
            }
        }
    }
}
