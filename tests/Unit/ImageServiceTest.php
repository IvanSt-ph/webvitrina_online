<?php

namespace Tests\Unit;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;
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

    public function test_upload_rejects_image_exceeding_max_width_without_decoding_or_storing_it(): void
    {
        Storage::fake('public');

        $service = new class extends ImageService
        {
            public bool $decodeAttempted = false;

            protected function decode(UploadedFile $file): ImageInterface
            {
                $this->decodeAttempted = true;

                return parent::decode($file);
            }
        };

        try {
            $service->upload($this->pngHeader('too-wide.png', 8001, 1), 'products/test');
            $this->fail('Unsafe image should be rejected.');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        $this->assertFalse($service->decodeAttempted);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_rejects_image_exceeding_max_height_without_decoding_or_storing_it(): void
    {
        $this->assertRejectedWithoutPublicFile($this->pngHeader('too-tall.png', 1, 8001));
    }

    public function test_upload_rejects_image_exceeding_total_pixel_limit_without_decoding_or_storing_it(): void
    {
        $this->assertRejectedWithoutPublicFile($this->pngHeader('too-many-pixels.png', 5000, 3201));
    }

    public function test_upload_rejects_malformed_image_without_public_fallback(): void
    {
        $this->assertRejectedWithoutPublicFile(
            UploadedFile::fake()->createWithContent('broken.jpg', 'not an image')
        );
    }

    public function test_upload_keeps_filesize_limit_as_separate_guard(): void
    {
        $this->assertRejectedWithoutPublicFile(
            UploadedFile::fake()->image('too-large.jpg', 10, 10)->size(8193)
        );
    }

    public function test_processing_failure_does_not_store_original_as_public_fallback(): void
    {
        Storage::fake('public');

        $service = new class extends ImageService
        {
            protected function uploadOptimized(UploadedFile $file, string $dir): string
            {
                throw new RuntimeException('forced image processing failure');
            }
        };

        try {
            $service->upload(UploadedFile::fake()->image('valid.jpg', 100, 100), 'products/test');
            $this->fail('The processing exception should be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced image processing failure', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_accepts_jpeg_png_and_webp_images(): void
    {
        Storage::fake('public');

        foreach (['jpg', 'png', 'webp'] as $extension) {
            $path = app(ImageService::class)->upload(
                UploadedFile::fake()->image("normal.{$extension}", 640, 480),
                'products/formats'
            );

            Storage::disk('public')->assertExists($path);
            Storage::disk('public')->assertExists(ImageService::thumbPath($path));
        }
    }

    private function assertRejectedWithoutPublicFile(UploadedFile $file): void
    {
        Storage::fake('public');

        try {
            app(ImageService::class)->upload($file, 'products/test');
            $this->fail('Unsafe image should be rejected.');
        } catch (InvalidArgumentException) {
            // Expected: metadata validation rejects the file before GD decode.
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function pngHeader(string $name, int $width, int $height): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($png);

        return UploadedFile::fake()->createWithContent(
            $name,
            substr_replace($png, pack('N', $width) . pack('N', $height), 16, 8)
        );
    }
}
