<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    public function test_upload_creates_webp_medium_and_thumb_versions(): void
    {
        Storage::fake('public');

        $path = app(ImageService::class)->upload(
            UploadedFile::fake()->image('product.jpg', 1600, 1200),
            'products/test'
        );

        $thumb = ImageService::thumbPath($path);

        $this->assertStringStartsWith('products/test/medium/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('products/test/thumb/', $thumb);
        $this->assertStringEndsWith('.webp', $thumb);

        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists($thumb);
    }
}
