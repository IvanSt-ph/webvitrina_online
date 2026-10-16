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

class ReviewImagePrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('review_images');
    }

    public function test_approved_review_image_is_served_through_a_non_cacheable_protected_route(): void
    {
        [, , $image] = $this->reviewImage(Review::STATUS_APPROVED);
        Storage::disk('review_images')->put($image->path, $this->imageContents());
        Storage::disk('review_images')->put(ImageService::thumbPath($image->path), $this->imageContents());

        $response = $this->get($image->url)->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->get($image->thumb_url)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_pending_and_rejected_images_are_visible_only_to_the_author_and_admin(): void
    {
        foreach ([Review::STATUS_PENDING, Review::STATUS_REJECTED] as $status) {
            [$author, $review, $image] = $this->reviewImage($status);
            Storage::disk('review_images')->put($image->path, $this->imageContents());
            $outsider = User::factory()->create(['role' => 'buyer']);
            $admin = User::factory()->create(['role' => 'admin']);

            $this->get($image->url)->assertNotFound();
            $this->actingAs($outsider)->get($image->url)->assertNotFound();
            $this->actingAs($author)->get($image->url)->assertOk();
            $this->actingAs($admin)->get($image->url)->assertOk();
            auth()->logout();
        }
    }

    public function test_soft_deleted_review_image_is_hidden_from_everyone_and_restore_reapplies_status_rules(): void
    {
        [$author, $review, $image] = $this->reviewImage(Review::STATUS_APPROVED);
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('review_images')->put($image->path, $this->imageContents());

        Review::withoutEvents(fn () => $review->delete());

        $this->get($image->url)->assertNotFound();
        $this->actingAs($author)->get($image->url)->assertNotFound();
        $this->actingAs($admin)->get($image->url)->assertNotFound();
        Storage::disk('review_images')->assertExists($image->path);

        Review::withoutEvents(fn () => $review->restore());
        $this->get($image->url)->assertOk();
    }

    public function test_the_same_url_is_reauthorized_after_moderation_status_changes(): void
    {
        [$author, $review, $image] = $this->reviewImage(Review::STATUS_APPROVED);
        Storage::disk('review_images')->put($image->path, $this->imageContents());

        $this->get($image->url)->assertOk();
        Review::withoutEvents(fn () => $review->update(['status' => Review::STATUS_REJECTED]));

        $this->get($image->url)->assertNotFound();
        $this->actingAs($author)->get($image->url)->assertOk();
    }

    public function test_legacy_public_file_uses_the_protected_route_and_unsafe_database_path_is_rejected(): void
    {
        [, , $legacy] = $this->reviewImage(Review::STATUS_APPROVED);
        Storage::disk('public')->put($legacy->path, $this->imageContents());
        $this->get($legacy->url)->assertOk();

        [, , $unsafe] = $this->reviewImage(Review::STATUS_APPROVED, 'reviews/../../private/secret.webp');
        Storage::disk('review_images')->put('private/secret.webp', 'secret');
        $this->get($unsafe->url)->assertNotFound();
    }

    public function test_non_raster_content_is_not_served_even_from_an_allowed_review_path(): void
    {
        [, , $image] = $this->reviewImage(Review::STATUS_APPROVED);
        Storage::disk('review_images')->put($image->path, '<svg onload="alert(1)"></svg>');

        $this->get($image->url)->assertNotFound();
    }

    /** @return array{User, Review, ReviewImage} */
    private function reviewImage(string $status, ?string $path = null): array
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $author = User::factory()->create(['role' => 'buyer']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Private review image',
            'slug' => 'private-review-image-'.uniqid(),
            'sku' => 'PRI-'.uniqid(),
            'price' => 100,
            'currency_base' => 'PRB',
            'stock' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $review = Review::withoutEvents(fn () => Review::create([
            'user_id' => $author->id,
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'Image access test',
            'status' => $status,
        ]));
        $image = ReviewImage::create([
            'review_id' => $review->id,
            'path' => $path ?? 'reviews/2026/10/'.uniqid().'.webp',
        ]);

        return [$author, $review, $image];
    }

    private function imageContents(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        );
    }
}
