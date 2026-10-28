<?php

namespace Tests\Feature;

use App\Models\{AdCampaign, AdSlot, Order, Product, Review, User};
use App\Repositories\ProductRepository;
use App\Services\ReviewModerationService;
use App\Services\ReviewRatingCache;
use Illuminate\Foundation\Testing\{RefreshDatabase, RefreshDatabaseState};
use Illuminate\Support\Facades\{Cache, DB, Storage};
use Tests\Support\ReviewFixtures;
use Tests\TestCase;

class PublicReviewRatingTest extends TestCase
{
    use RefreshDatabase, ReviewFixtures;

    public function beginDatabaseTransaction(): void
    {
        // Exercise real afterCommit cache invalidation, without an outer test transaction.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_catalog_category_ads_and_recommendations_only_use_approved_reviews(): void
    {
        [, , $product, $review] = $this->reviewFixture();
        $review->update(['status' => Review::STATUS_APPROVED]);
        foreach ([Review::STATUS_PENDING, Review::STATUS_REJECTED] as $status) {
            Review::create(['user_id' => User::factory()->create()->id, 'product_id' => $product->id,
                'rating' => 5, 'body' => 'Hidden', 'status' => $status]);
        }
        $other = Product::create([
            'user_id' => $product->user_id, 'category_id' => $product->category_id,
            'title' => 'Better approved rating', 'price' => 100, 'stock' => 1, 'status' => Product::STATUS_ACTIVE,
        ]);
        Review::create(['user_id' => User::factory()->create()->id, 'product_id' => $other->id,
            'rating' => 3, 'status' => Review::STATUS_APPROVED]);
        foreach ([AdSlot::HOME_FEATURED_PRODUCTS, AdSlot::CATEGORY_FEATURED_PRODUCTS] as $key) {
            $slot = AdSlot::firstOrCreate(['key' => $key], ['name' => $key, 'placement' => 'test', 'is_active' => true]);
            $slot->update(['is_active' => true]);
            AdCampaign::create(['ad_slot_id' => $slot->id, 'product_id' => $product->id,
                'target_type' => AdCampaign::TYPE_PRODUCT, 'title' => 'Rated campaign', 'is_active' => true]);
        }
        Cache::forget('ads.home');
        foreach ([1, 2] as $attempt) { // Cold query and warm cache must agree.
            $home = $this->get(route('home'))->assertOk();
            $this->assertRating($home->viewData('products')->firstWhere('id', $product->id), 2, 1);
            $this->assertRating($home->viewData('homeAdCampaigns')->get(AdSlot::HOME_FEATURED_PRODUCTS)->first()->product, 2, 1);
        }
        $category = $this->get(route('category.show', [$product->category->slug, 'sort' => 'rating']))->assertOk();
        $this->assertSame($other->id, $category->viewData('products')->first()->id);
        $this->assertRating($category->viewData('products')->firstWhere('id', $product->id), 2, 1);
        $this->assertRating($category->viewData('categoryAdCampaigns')->first()->product, 2, 1);
        $recommendations = $this->get(route('recommendations.index'))->assertOk();
        $this->assertRating($recommendations->viewData('products')->firstWhere('id', $product->id), 2, 1);
        $this->assertRating(app(ProductRepository::class)->getProductBySlugOrId($product->slug), 2, 1);
    }

    public function test_moderation_and_deletion_invalidate_caches_after_commit_and_refresh_related_counts(): void
    {
        [, $admin, $product, $review] = $this->reviewFixture();
        $repository = app(ProductRepository::class);
        $other = Product::create([
            'user_id' => $product->user_id, 'category_id' => $product->category_id,
            'title' => 'Related source', 'price' => 100, 'stock' => 1, 'status' => Product::STATUS_ACTIVE,
        ]);
        $this->assertSame(0, (int) $repository->getRelatedProducts($other)->firstWhere('id', $product->id)->reviews_count);
        $keys = ['ads.home', 'products.home.high_rating_recommendations', 'products.home.catalog_recommendations'];
        foreach ([Review::STATUS_APPROVED, Review::STATUS_REJECTED, Review::STATUS_APPROVED, 'delete'] as $status) {
            $repository->getProductBySlugOrId($product->slug);
            foreach ($keys as $key) {
                Cache::put($key, 'cached before transaction', 300);
            }
            DB::transaction(function () use ($status, $review, $admin, $keys) {
                if ($status === 'delete') {
                    $review->fresh()->delete();
                } else {
                    app(ReviewModerationService::class)->moderate([$review->id],
                        [$review->id => $review->fresh()->moderationFingerprint()], $status, $admin->id, 'Reason');
                }
                foreach ($keys as $key) {
                    $this->assertSame('cached before transaction', Cache::get($key));
                }
            });
            foreach ($keys as $key) {
                $this->assertFalse(Cache::has($key));
            }
            $count = $status === Review::STATUS_APPROVED ? 1 : 0;
            $this->assertFalse(Cache::has('product_page:'.$product->slug));
            $current = $repository->getProductBySlugOrId($product->slug);
            $this->assertSame($count, (int) $current->reviews_count);
            $this->assertSame($count, (int) $repository->getRelatedProducts($other)->firstWhere('id', $product->id)->reviews_count);
        }
    }

    public function test_a_cache_fill_started_before_rejection_cannot_repopulate_a_valid_old_rating(): void
    {
        [, $admin, $product, $review] = $this->reviewFixture();
        $review->update(['status' => Review::STATUS_APPROVED]);
        $key = 'product_page:'.$product->slug;
        $repository = app(ProductRepository::class);
        $old = $repository->getProductBySlugOrId($product->slug);
        Cache::forget($key);
        // Deterministic interleaving: the reader computed its result, moderation commits,
        // then the original reader finally writes that old result into the cache.
        ReviewRatingCache::remember($key, 600, function () use ($old, $review, $admin) {
            app(ReviewModerationService::class)->moderate([$review->id],
                [$review->id => $review->moderationFingerprint()], Review::STATUS_REJECTED, $admin->id, 'Reason');
            return $old;
        });
        $this->assertSame(0, (int) $repository->getProductBySlugOrId($product->slug)->reviews_count);
        // Unversioned entries left by the previous release must also be rejected.
        Cache::put($key, $old, 600);
        $this->assertSame(0, (int) $repository->getProductBySlugOrId($product->slug)->reviews_count);
    }

    public function test_account_deletion_invalidates_homepage_ratings_even_without_review_model_events(): void
    {
        Storage::fake('public');
        Storage::fake('review_images');
        [$buyer, , $product, $review] = $this->reviewFixture();
        $review->update(['status' => Review::STATUS_APPROVED]);
        DB::table('orders')->where('user_id', $buyer->id)->update(['status' => Order::STATUS_COMPLETED]);
        $before = $this->get(route('home'))->assertOk()->viewData('recommendedCatalogProducts')->firstWhere('id', $product->id);
        $this->assertRating($before, 2, 1);
        $buyer->delete();
        $after = $this->get(route('home'))->assertOk()->viewData('recommendedCatalogProducts')->firstWhere('id', $product->id);
        $this->assertNotNull($after);
        $this->assertSame(0, (int) $after->reviews_count);
        $this->assertNull($after->reviews_avg_rating);
    }

    private function assertRating(?Product $product, float $average, int $count): void
    {
        $this->assertNotNull($product);
        $this->assertSame($average, (float) $product->reviews_avg_rating);
        $this->assertSame($count, (int) $product->reviews_count);
    }
}
