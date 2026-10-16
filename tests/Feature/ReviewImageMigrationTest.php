<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewImageMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('review_images');
    }

    public function test_dry_run_does_not_copy_or_delete_legacy_files(): void
    {
        $image = $this->legacyImage();

        $this->artisan('review-images:migrate-private')->assertSuccessful();

        Storage::disk('public')->assertExists($image->path);
        Storage::disk('review_images')->assertMissing($image->path);
    }

    public function test_apply_is_resumable_idempotent_and_cleanup_requires_verified_identical_files(): void
    {
        $image = $this->legacyImage();
        $thumb = ImageService::thumbPath($image->path);
        Storage::disk('review_images')->put('.migration/interrupted.part', 'partial');

        $this->artisan('review-images:migrate-private', ['--apply' => true])->assertSuccessful();
        Storage::disk('review_images')->assertMissing('.migration/interrupted.part');
        Storage::disk('public')->assertExists($image->path);
        Storage::disk('review_images')->assertExists($image->path);
        $this->assertSame(
            Storage::disk('public')->get($image->path),
            Storage::disk('review_images')->get($image->path),
        );

        $this->artisan('review-images:migrate-private', ['--apply' => true])->assertSuccessful();
        $this->artisan('review-images:migrate-private', [
            '--apply' => true,
            '--delete-public' => true,
        ])->assertSuccessful();

        Storage::disk('public')->assertMissing($image->path);
        Storage::disk('public')->assertMissing($thumb);
        Storage::disk('review_images')->assertExists($image->path);
        Storage::disk('review_images')->assertExists($thumb);
    }

    public function test_checksum_mismatch_fails_without_deleting_any_public_copy(): void
    {
        $image = $this->legacyImage();
        Storage::disk('review_images')->put($image->path, 'different');

        $this->artisan('review-images:migrate-private', [
            '--apply' => true,
            '--delete-public' => true,
        ])->assertFailed();

        Storage::disk('public')->assertExists($image->path);
        Storage::disk('review_images')->assertExists($image->path);
    }

    public function test_public_cleanup_refuses_to_hide_unreferenced_files_silently(): void
    {
        $image = $this->legacyImage();
        Storage::disk('public')->put('reviews/orphan.webp', 'unreferenced');

        $this->artisan('review-images:migrate-private', [
            '--apply' => true,
            '--delete-public' => true,
        ])->assertFailed();

        Storage::disk('public')->assertExists($image->path);
        Storage::disk('public')->assertExists('reviews/orphan.webp');
        Storage::disk('review_images')->assertExists($image->path);
    }

    private function legacyImage(): ReviewImage
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Legacy review image',
            'slug' => 'legacy-review-image-'.uniqid(),
            'sku' => 'LRI-'.uniqid(),
            'price' => 100,
            'currency_base' => 'PRB',
            'stock' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $review = Review::withoutEvents(fn () => Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'status' => Review::STATUS_APPROVED,
        ]));
        $image = ReviewImage::create([
            'review_id' => $review->id,
            'path' => 'reviews/2026/10/'.uniqid().'.webp',
        ]);
        Storage::disk('public')->put($image->path, 'medium');
        Storage::disk('public')->put(ImageService::thumbPath($image->path), 'thumb');

        return $image;
    }
}
