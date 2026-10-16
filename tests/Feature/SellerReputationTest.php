<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellerReputationTest extends TestCase
{
    use DatabaseTruncation;

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    public function test_only_approved_reviews_affect_the_right_seller_and_survive_product_soft_delete(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Rated shop');
        [$otherSeller, $otherShop] = $this->sellerWithShop('Other shop');
        [$unratedSeller, $unratedShop] = $this->sellerWithShop('Unrated shop');
        $product = $this->product($seller, 'rated-product');
        $otherProduct = $this->product($otherSeller, 'other-product');
        $unratedProduct = $this->product($unratedSeller, 'unrated-product');

        $this->review($product, 5, Review::STATUS_APPROVED);
        $this->review($product, 1, Review::STATUS_PENDING);
        $this->review($product, 2, Review::STATUS_REJECTED);
        $this->review($otherProduct, 2, Review::STATUS_APPROVED);
        $this->review($unratedProduct, 5, Review::STATUS_PENDING);

        $this->assertShopRating($shop, 5.00, 1);
        $this->assertShopRating($otherShop, 2.00, 1);
        $this->assertShopRating($unratedShop, 0.00, 0);
        $this->assertSame('new', $unratedShop->fresh()->seller_reputation);

        $product->delete();

        $this->assertShopRating($shop, 5.00, 1);
        $this->assertSame(1, $seller->approvedProductReviews()->count());
    }

    public function test_completed_order_allows_one_review_for_a_soft_deleted_product_only_to_its_buyer(): void
    {
        [$seller] = $this->sellerWithShop('Deleted product shop');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $outsider = User::factory()->create(['role' => 'buyer']);
        $product = $this->product($seller, 'deleted-reviewed-product');
        $otherProduct = $this->product($seller, 'not-in-order');
        $order = $this->legacyOrder($buyer, $seller, Order::STATUS_COMPLETED);
        $this->item($order, $product);
        $product->delete();

        $this->actingAs($outsider)
            ->post(route('orders.reviews.store', [$order, $product->id]), ['rating' => 1])
            ->assertForbidden();

        $this->actingAs($buyer)
            ->post(route('orders.reviews.store', [$order, $otherProduct->id]), ['rating' => 1])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->post(route('orders.reviews.store', [$order, $product->id]), [
                'rating' => 4,
                'body' => 'First version',
            ])->assertRedirect();

        $this->actingAs($buyer)
            ->post(route('orders.reviews.store', [$order, $product->id]), [
                'rating' => 5,
                'body' => 'Updated version',
            ])->assertRedirect();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'Updated version',
            'status' => Review::STATUS_PENDING,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted($product);
        $this->get(route('product.show', $product->slug))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee($product->title);
        $this->actingAs($buyer)
            ->get(route('orders.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee($order->number);
        $this->actingAs($buyer)
            ->get(route('orders.index', ['tab' => 'action']))
            ->assertOk()
            ->assertViewHas('actionCount', 0)
            ->assertDontSee($order->number);
    }

    public function test_pickup_review_requires_supported_completed_receipt_and_matching_order_item(): void
    {
        [$seller] = $this->sellerWithShop('Pickup review shop');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->product($seller, 'pickup-review-product');
        $forgedProduct = $this->product($seller, 'forged-review-product');
        $order = $this->pickupOrder($buyer, $seller, Order::STATUS_DELIVERED, [
            'buyer_confirmed_at' => null,
        ]);
        $this->item($order, $product);

        $this->actingAs($buyer)
            ->postJson(route('orders.reviews.store', [$order, $product->id]), ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review');

        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_COMPLETED,
            'buyer_confirmed_at' => null,
            'completed_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->postJson(route('orders.reviews.store', [$order, $product->id]), ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review');

        DB::table('orders')->where('id', $order->id)->update(['buyer_confirmed_at' => now()]);
        $order->refresh();

        $this->actingAs($buyer)
            ->post(route('orders.reviews.store', [$order, $forgedProduct->id]), ['rating' => 5])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->post(route('orders.reviews.store', [$order, $product->id]), ['rating' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    public function test_admin_status_changes_recalculate_rating_without_touching_another_seller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$seller, $shop] = $this->sellerWithShop('Moderated shop');
        [$otherSeller, $otherShop] = $this->sellerWithShop('Unaffected shop');
        $review = $this->review($this->product($seller, 'moderated-product'), 2, Review::STATUS_PENDING);
        $this->review($this->product($otherSeller, 'unaffected-product'), 5, Review::STATUS_APPROVED);

        $this->actingAs($admin)
            ->postJson(route('admin.reviews.approve', $review))
            ->assertOk();

        $this->assertShopRating($shop, 2.00, 1);
        $this->assertShopRating($otherShop, 5.00, 1);

        $this->actingAs($admin)
            ->postJson(route('admin.reviews.reject', $review), ['reason' => 'Rejected after review'])
            ->assertOk();

        $this->assertShopRating($shop, 0.00, 0);
        $this->assertShopRating($otherShop, 5.00, 1);
    }

    public function test_review_soft_delete_restore_and_force_delete_keep_statistics_consistent(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Review lifecycle shop');
        $review = $this->review($this->product($seller, 'review-lifecycle-product'), 4, Review::STATUS_APPROVED);
        $this->assertShopRating($shop, 4.00, 1);

        $review->delete();
        $this->assertSoftDeleted($review);
        $this->assertShopRating($shop, 0.00, 0);

        $review->restore();
        $this->assertShopRating($shop, 4.00, 1);

        $review->forceDelete();
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertShopRating($shop, 0.00, 0);
    }

    public function test_resubmitting_a_soft_deleted_review_restores_the_unique_row_as_pending(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Resubmission shop');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $moderator = User::factory()->create(['role' => 'admin']);
        $product = $this->product($seller, 'resubmitted-product');
        $order = $this->legacyOrder($buyer, $seller, Order::STATUS_DELIVERED);
        $this->item($order, $product);
        $review = Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 2,
            'status' => Review::STATUS_APPROVED,
            'rejection_reason' => 'Stale moderation reason',
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
        $review->delete();

        $this->actingAs($buyer)
            ->post(route('review.store', $product), ['rating' => 5, 'body' => 'Resubmitted'])
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'status' => Review::STATUS_PENDING,
            'rejection_reason' => null,
            'moderated_by' => null,
            'moderated_at' => null,
            'deleted_at' => null,
        ]);
        $this->assertShopRating($shop, 0.00, 0);
    }

    public function test_bulk_moderation_recalculates_each_affected_seller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$firstSeller, $firstShop] = $this->sellerWithShop('First bulk shop');
        [$secondSeller, $secondShop] = $this->sellerWithShop('Second bulk shop');
        $first = $this->review($this->product($firstSeller, 'first-bulk-product'), 5, Review::STATUS_PENDING);
        $second = $this->review($this->product($secondSeller, 'second-bulk-product'), 3, Review::STATUS_PENDING);

        $this->actingAs($admin)
            ->postJson(route('admin.reviews.bulk'), [
                'ids' => [$first->id, $second->id],
                'action' => 'approve',
            ])->assertOk()->assertJson(['updated' => 2]);

        $this->assertShopRating($firstShop, 5.00, 1);
        $this->assertShopRating($secondShop, 3.00, 1);
    }

    public function test_product_owner_change_moves_reputation_without_changing_historical_order_seller(): void
    {
        [$oldSeller, $oldShop] = $this->sellerWithShop('Original owner shop');
        [$newSeller, $newShop] = $this->sellerWithShop('New owner shop');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->product($oldSeller, 'transferred-product');
        $order = $this->legacyOrder($buyer, $oldSeller, Order::STATUS_COMPLETED);
        $this->item($order, $product);
        $this->review($product, 4, Review::STATUS_APPROVED);

        $this->assertShopRating($oldShop, 4.00, 1);
        $this->assertShopRating($newShop, 0.00, 0);

        $product->update(['user_id' => $newSeller->id]);

        $this->assertShopRating($oldShop, 0.00, 0);
        $this->assertSame('new', $oldShop->fresh()->seller_reputation);
        $this->assertShopRating($newShop, 4.00, 1);
        $this->assertSame($oldSeller->id, $order->fresh()->seller_id);
    }

    public function test_public_shop_lists_approved_deleted_product_review_without_product_identity_or_link(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Historical reviews shop');
        $product = $this->product($seller, 'private-deleted-title');
        $review = $this->review($product, 5, Review::STATUS_APPROVED);
        $productUrl = route('product.show', $product->slug);
        $productTitle = $product->title;
        $product->delete();

        $this->get(route('seller.show', $shop->slug))
            ->assertOk()
            ->assertSee($review->body)
            ->assertSee('Исторический отзыв · товар снят с публикации')
            ->assertDontSee($productTitle)
            ->assertDontSee($productUrl, false);

        $this->actingAs($seller)
            ->get(route('seller.cabinet'))
            ->assertOk()
            ->assertViewHas('reputationProgress', fn (array $progress): bool =>
                (float) $progress['rating'] === 5.0
            )
            ->assertViewHas('stats', fn (array $stats): bool => collect($stats)->contains(
                fn (array $stat): bool => $stat['label'] === 'Комментариев (отзывов)'
                    && $stat['value'] === '1'
            ));

        $this->assertShopRating($shop, 5.00, 1);
    }

    public function test_recalculation_command_is_dry_run_safe_counts_orders_and_is_idempotent(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Command shop');
        $product = $this->product($seller, 'command-product');
        $deletedProduct = $this->product($seller, 'command-deleted-product');
        $this->review($product, 5, Review::STATUS_APPROVED);
        $this->review($deletedProduct, 3, Review::STATUS_APPROVED);
        $this->review($product, 1, Review::STATUS_PENDING);
        $deletedProduct->delete();

        $buyer = User::factory()->create(['role' => 'buyer']);
        $delivered = $this->legacyOrder($buyer, $seller, Order::STATUS_DELIVERED);
        $this->item($delivered, $product);
        $this->item($delivered, $product, 2);
        $this->legacyOrder($buyer, $seller, Order::STATUS_COMPLETED);
        $this->pickupOrder($buyer, $seller, Order::STATUS_COMPLETED, [
            'buyer_confirmed_at' => now(),
            'payment_status' => Order::PAYMENT_SELLER_CONFIRMED,
            'paid_at' => now(),
            'completed_at' => now(),
        ]);
        $this->pickupOrder($buyer, $seller, Order::STATUS_COMPLETED, [
            'buyer_confirmed_at' => now(),
            'payment_status' => Order::PAYMENT_UNPAID,
            'paid_at' => null,
            'completed_at' => now(),
        ]);

        $shop->forceFill([
            'rating' => 1,
            'reviews_count' => 99,
            'sales_count' => 99,
            'seller_reputation' => 'top',
        ])->saveQuietly();

        $this->artisan('shops:recalculate-reputation')->assertSuccessful();
        $this->assertShopValues($shop, 1.00, 99, 99, 'top');

        $this->artisan('shops:recalculate-reputation', ['--apply' => true])->assertSuccessful();
        $this->assertShopValues($shop, 4.00, 2, 3, 'new');
        $updatedAt = $shop->fresh()->getRawOriginal('updated_at');

        $this->travel(2)->seconds();
        $this->artisan('shops:recalculate-reputation', ['--apply' => true])->assertSuccessful();
        $this->assertSame($updatedAt, $shop->fresh()->getRawOriginal('updated_at'));
    }

    private function sellerWithShop(string $name): array
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create(['user_id' => $seller->id, 'name' => $name]);

        return [$seller, $shop];
    }

    private function product(User $seller, string $slug): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'title' => str_replace('-', ' ', $slug),
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'price' => 100,
            'currency_base' => 'PRB',
            'stock' => 10,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    private function review(Product $product, int $rating, string $status): Review
    {
        return Review::create([
            'user_id' => User::factory()->create(['role' => 'buyer'])->id,
            'product_id' => $product->id,
            'rating' => $rating,
            'body' => "Rating {$rating}",
            'status' => $status,
        ]);
    }

    private function legacyOrder(User $buyer, User $seller, string $status): Order
    {
        return Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => Order::generateNumber(),
            'status' => $status,
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'total_price' => 100,
            'currency' => 'PRB',
        ]);
    }

    private function pickupOrder(User $buyer, User $seller, string $status, array $state = []): Order
    {
        $order = $this->legacyOrder($buyer, $seller, Order::STATUS_PENDING);
        DB::table('orders')->where('id', $order->id)->update(array_merge([
            'workflow_version' => Order::WORKFLOW_PICKUP,
            'status' => $status,
            'payment_status' => Order::PAYMENT_UNPAID,
        ], $state));

        return $order->refresh();
    }

    private function item(Order $order, Product $product, int $quantity = 1): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => 100,
            'total' => 100 * $quantity,
            'source_price' => 100,
            'source_currency' => 'PRB',
            'exchange_rate' => 1,
        ]);
    }

    private function assertShopRating(Shop $shop, float $rating, int $reviews): void
    {
        $fresh = $shop->fresh();
        $this->assertSame($rating, (float) $fresh->rating);
        $this->assertSame($reviews, (int) $fresh->reviews_count);
    }

    private function assertShopValues(Shop $shop, float $rating, int $reviews, int $sales, string $reputation): void
    {
        $fresh = $shop->fresh();
        $this->assertSame($rating, (float) $fresh->rating);
        $this->assertSame($reviews, (int) $fresh->reviews_count);
        $this->assertSame($sales, (int) $fresh->sales_count);
        $this->assertSame($reputation, $fresh->seller_reputation);
    }
}
