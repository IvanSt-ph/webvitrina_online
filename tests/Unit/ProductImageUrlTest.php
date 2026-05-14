<?php

namespace Tests\Unit;

use App\Models\Product;
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
        $product = new Product([
            'image' => 'products/2026/05/medium/product.webp',
        ]);

        $this->assertStringEndsWith('/storage/products/2026/05/medium/product.webp', $product->image_url);
        $this->assertStringEndsWith('/storage/products/2026/05/thumb/product.webp', $product->image_thumb_url);
    }
}
