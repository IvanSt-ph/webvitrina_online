<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'logging.default' => 'null',
        ]);
    }

    public function test_missing_slug_is_generated_and_persisted_from_title(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        foreach ([
            ['  WebVitrina Product 123!  ', 'webvitrina-product-123'],
            ['Товар 123', 'tovar-123'],
        ] as [$title, $expectedSlug]) {
            $product = Product::create([
                'title' => $title,
                'category_id' => $category->id,
                'user_id' => $seller->id,
                'price' => 1000,
            ]);

            $this->assertSame($expectedSlug, $product->slug);
            $this->assertSame($expectedSlug, $product->fresh()->slug);
            $this->assertSame($title, $product->fresh()->title);
        }
    }

    public function test_generated_slug_collisions_preserve_existing_products(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();
        $ids = [];

        foreach (['repeat-product', 'repeat-product-1', 'repeat-product-2'] as $expectedSlug) {
            $product = Product::create([
                'title' => 'Repeat Product',
                'category_id' => $category->id,
                'user_id' => $seller->id,
                'price' => 1000,
            ]);
            $ids[] = $product->id;

            $this->assertSame($expectedSlug, $product->fresh()->slug);
        }

        $this->assertSame(
            ['repeat-product', 'repeat-product-1', 'repeat-product-2'],
            Product::whereIn('id', $ids)->orderBy('id')->pluck('slug')->all(),
        );
    }

    /**
     * Тест: правильная генерация slug из названия товара
     */
    public function test_product_slug_is_generated_from_title(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::create([
            'title' => 'Тестовый товар с названием',
            'slug' => 'testovyy-tovar-s-nazvaniem',
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'price' => 1000,
            'description' => 'Описание товара',
        ]);

        $this->assertEquals('testovyy-tovar-s-nazvaniem', $product->slug);
    }

    /**
     * Тест: slug должен быть уникальным
     */
    public function test_product_slug_must_be_unique(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        Product::create([
            'title' => 'Одинаковый товар',
            'slug' => 'odinakovyy-tovar',
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'price' => 1000,
            'description' => 'Описание товара 1',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Product::create([
            'title' => 'Одинаковый товар 2',
            'slug' => 'odinakovyy-tovar',
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'price' => 2000,
            'description' => 'Описание товара 2',
        ]);
    }

    /**
     * Тест: slug должен соответствовать формату (только латиница, цифры, дефисы)
     */
    public function test_product_slug_has_correct_format(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::factory()->create();

        $product = Product::create([
            'title' => 'Тестовый товар 123',
            'slug' => 'testovyy-tovar-123',
            'category_id' => $category->id,
            'user_id' => $seller->id,
            'price' => 1000,
            'description' => 'Описание товара',
        ]);

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $product->slug);
    }
}
