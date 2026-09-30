<?php
namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUrlTest extends TestCase
{
    public function test_default_and_empty_paths_use_placeholder(): void
    {
        Storage::shouldReceive('disk')->never();
        foreach (['', 'default/no-image.png', '/storage/default/no-image.png', 'placeholder.png'] as $path) {
            $product = new Product(['image' => $path]);
            $this->assertSame(asset('images/image-placeholder.svg'), $product->image_url);
            $this->assertSame([$product->image_url], $product->image_thumb_candidates);
        }
    }

    public function test_medium_and_legacy_original_have_bounded_browser_hierarchy(): void
    {
        Storage::shouldReceive('disk')->never();
        $this->assertSame([
            asset('storage/products/thumb/photo.webp'), asset('storage/products/medium/photo.webp'), asset('images/image-placeholder.svg'),
        ], Product::storageThumbCandidates('products/medium/photo.webp'));
        $this->assertSame([
            asset('storage/thumb/photo.webp'), asset('storage/photo.jpg'), asset('images/image-placeholder.svg'),
        ], Product::storageThumbCandidates('photo.jpg'));
    }

    public function test_double_storage_prefix_is_not_normalized_twice(): void
    {
        $this->assertSame(asset('storage/storage/photo.jpg'), Product::storageImageUrl('/storage/storage/photo.jpg'));
        $this->assertSame(asset('storage/storage/photo.jpg'), Product::storageThumbCandidates('/storage/storage/photo.jpg')[1]);
    }
}
