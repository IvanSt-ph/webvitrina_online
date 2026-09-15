<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_schema_preserves_text_without_breaking_out_of_script(): void
    {
        $title = 'Товар </script><script>alert("schema")</script> & special';
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => $title]);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => $title,
            'sku' => 'SCHEMA-TEST',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $response = $this->get(route('product.show', $product->slug))->assertOk();
        $schema = $this->decodeSchema($response->getContent());

        $this->assertSame('Product', $schema['@type']);
        $this->assertSame($title, $schema['name']);
        $this->assertSame($title, $schema['brand']['name']);
    }

    public function test_all_category_templates_render_valid_safe_schema(): void
    {
        $name = 'Раздел </script><script>alert("schema")</script> & special';
        $parent = Category::factory()->create(['name' => $name]);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => $name]);

        foreach ([
            [route('category.index'), 'Категории'],
            [route('category.show', $parent->slug), $name],
            [route('category.show', $child->slug), $name],
        ] as [$url, $expectedName]) {
            $response = $this->get($url)->assertOk();
            $schema = $this->decodeSchema($response->getContent());
            $this->assertSame('CollectionPage', $schema['@type']);
            $this->assertSame($expectedName, $schema['name']);
        }
    }

    private function decodeSchema(string $html): array
    {
        $this->assertStringNotContainsString('<script>alert("schema")</script>', $html);
        $this->assertSame(1, preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches));
        $schema = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org', $schema['@context']);

        return $schema;
    }
}
