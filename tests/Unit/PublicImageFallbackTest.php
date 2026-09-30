<?php
namespace Tests\Unit;

use App\Models\{Banner, Category, Product, ReviewImage, Shop, User};
use App\Support\PublicImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicImageFallbackTest extends TestCase
{
    public function test_display_helpers_never_touch_storage_even_for_large_catalog(): void
    {
        Storage::shouldReceive('disk')->never();
        for ($i = 0; $i < 100; $i++) $this->assertCount(3, Product::storageThumbCandidates("products/medium/$i.webp"));
    }

    public function test_unsafe_paths_are_rejected_and_trusted_root_thumb_is_generated(): void
    {
        foreach ([null, '', ' ', '../secret.jpg', 'products/../secret.jpg', './thumb/photo.webp',
            '%2e%2e/secret.jpg', 'https://example.test/a.jpg', '//host/a.jpg', 'C:\\secret.jpg',
            "products/\0.jpg", '/etc/passwd', 'products//photo.jpg'] as $path) {
            $this->assertNull(PublicImage::path($path));
        }
        $this->assertSame('storage/photo.jpg', PublicImage::path('/storage/storage/photo.jpg'));
        $this->assertSame(asset('storage/thumb/photo.webp'), PublicImage::candidates('photo.jpg', thumb: true)[0]);
        $this->assertSame(asset('storage/products/old%20photo.jpg'), PublicImage::url('products/old photo.jpg'));
    }

    public function test_model_candidate_chains_preserve_local_and_legacy_values(): void
    {
        Storage::shouldReceive('disk')->never();
        $this->assertSame([
            asset('storage/categories/missing.jpg'), asset('storage/categories/icons/local.png'), asset('images/image-placeholder.svg'),
        ], (new Category(['image' => 'categories/missing.jpg', 'icon' => 'local.png']))->image_candidates);
        $this->assertSame([asset('images/avatar-placeholder.svg')], (new User(['avatar' => 'https://external.test/a.jpg']))->avatar_candidates);
        $this->assertSame(asset('storage/avatars/thumb/user.webp'), (new User(['avatar' => '/storage/avatars/medium/user.webp']))->avatar_url);
        $this->assertSame(asset('storage/shops/banner.jpg'), (new Shop(['banner' => 'shops/banner.jpg']))->banner_url);
        $this->assertSame(asset('storage/reviews/thumb/photo.webp'), (new ReviewImage(['path' => 'reviews/medium/photo.webp']))->thumb_url);
    }

    public function test_banner_display_candidates_do_not_claim_a_real_crop_source(): void
    {
        Storage::fake('public');
        $missing = new Banner(['image_desktop' => 'banners/missing.webp']);
        $this->assertSame(asset('storage/banners/missing.webp'), $missing->imageUrl());
        $this->assertSame(['main' => null, 'mobile' => null], $missing->cropSources());
        $form = view('admin.banners.form', ['banner' => $missing, 'errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('existingUrl: null', $form);
        $this->assertStringContainsString('images/image-placeholder.svg', $form);
        try {
            (new \App\Http\Controllers\Admin\BannerController())->update(
                \Illuminate\Http\Request::create('/admin/banners/1', 'POST', ['recrop_existing' => '1']),
                $missing,
            );
            $this->fail('Recrop without a physical source must fail validation.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('recrop_existing', $exception->errors());
        }
        $this->assertSame(asset('images/image-placeholder.svg'), (new Banner())->imageUrl());
        Storage::disk('public')->put('banners/desktop/live.webp', 'image');
        Storage::disk('public')->put('banners/mobile/live.webp', 'image');
        $real = new Banner(['image_desktop' => 'banners/desktop/live.webp', 'image_mobile' => 'banners/mobile/live.webp']);
        $this->assertSame(asset('storage/banners/desktop/live.webp'), $real->cropSources()['main']);
        $this->assertSame(asset('storage/banners/mobile/live.webp'), $real->cropSources()['mobile']);
        $this->assertStringNotContainsString('sale1.jpg', file_get_contents(resource_path('views/shop/index.blade.php')));
    }
}
