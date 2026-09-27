<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUrlTest extends TestCase
{
    public function test_default_product_image_uses_existing_default_file_for_thumb(): void
    {
        $product = new Product([
            'image' => 'default/no-image.png',
        ]);

        $this->assertStringEndsWith('/storage/default/no-image.png', $product->image_url);
        $this->assertSame($product->image_url, $product->image_thumb_url);
    }

    public function test_uploaded_product_image_uses_thumb_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/2026/05/medium/product.webp', 'image');
        Storage::disk('public')->put('products/2026/05/thumb/product.webp', 'thumbnail');
        $product = new Product([
            'image' => 'products/2026/05/medium/product.webp',
        ]);

        $this->assertStringEndsWith('/storage/products/2026/05/medium/product.webp', $product->image_url);
        $this->assertStringEndsWith('/storage/products/2026/05/thumb/product.webp', $product->image_thumb_url);
    }

    public function test_missing_thumbnail_falls_back_to_existing_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/medium/product.webp', 'image');
        $product = new Product(['image' => 'products/medium/product.webp']);

        $this->assertStringEndsWith('/storage/products/medium/product.webp', $product->image_url);
        $this->assertSame($product->image_url, $product->image_thumb_url);
    }

    public function test_missing_image_falls_back_to_default(): void
    {
        Storage::fake('public');
        $product = new Product(['image' => 'products/medium/missing.webp']);

        $this->assertStringEndsWith('/storage/default/no-image.png', $product->image_url);
        $this->assertSame($product->image_url, $product->image_thumb_url);
    }
}
