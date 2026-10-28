<?php

namespace Tests\Feature;

use App\Models\{Review, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ReviewFixtures;
use Tests\TestCase;

class ReviewModerationIntegrityTest extends TestCase
{
    use RefreshDatabase, ReviewFixtures;

    public static function changes(): array
    {
        $cases = [];
        foreach (['body', 'rating', 'images'] as $field) {
            foreach (['approve', 'reject', 'bulk-approve', 'bulk-reject'] as $action) {
                $cases[$field.' '.$action] = [$field, $action];
            }
        }
        return $cases;
    }

    #[DataProvider('changes')]
    public function test_stale_decisions_are_rejected_without_partial_moderation(string $field, string $action): void
    {
        Storage::fake('public');
        Storage::fake('review_images');
        [$buyer, $admin, $product, $review] = $this->reviewFixture();
        $old = $this->actingAs($admin)->getJson(route('admin.reviews.show', $review))->assertOk()->json('content_fingerprint');
        $unchanged = Review::create([
            'user_id' => User::factory()->create()->id, 'product_id' => $product->id,
            'rating' => 4, 'body' => 'Unchanged', 'status' => Review::STATUS_PENDING,
        ]);
        $data = ['rating' => 2, 'body' => 'Inspected text'];
        $data[$field] = match ($field) {
            'body' => 'Uninspected text', 'rating' => 5,
            'images' => [UploadedFile::fake()->image('replacement.jpg', 24, 24)],
        };
        $this->actingAs($buyer)->post(route('review.store', $product), $data)->assertRedirect();
        $this->assertNotSame($old, $review->fresh()->moderationFingerprint());
        $this->actingAs($admin);
        if (str_starts_with($action, 'bulk-')) {
            $this->postJson(route('admin.reviews.bulk'), [
                'ids' => [$unchanged->id, $review->id], 'action' => substr($action, 5), 'reason' => 'Reason',
                'fingerprints' => [$review->id => $old, $unchanged->id => $unchanged->moderationFingerprint()],
            ])->assertStatus(409);
        } else {
            $this->postJson(route('admin.reviews.'.$action, $review), [
                'content_fingerprint' => $old, 'reason' => 'Reason',
            ])->assertStatus(409);
        }
        foreach ([$review, $unchanged] as $item) {
            $this->assertSame(Review::STATUS_PENDING, $item->fresh()->status);
            $this->assertNull($item->fresh()->moderated_by);
        }
        $logAction = match ($action) {
            'approve' => 'review.approved', 'reject' => 'review.rejected',
            'bulk-approve' => 'review.bulk_approve', 'bulk-reject' => 'review.bulk_reject',
        };
        $this->assertDatabaseMissing('admin_activity_logs', ['action' => $logAction]);
    }

    public function test_fresh_snapshot_allows_moderation_and_missing_tokens_fail_closed(): void
    {
        [, $admin, , $review] = $this->reviewFixture();
        $this->actingAs($admin)->postJson(route('admin.reviews.approve', $review))->assertUnprocessable();
        $this->postJson(route('admin.reviews.bulk'), ['ids' => [$review->id], 'action' => 'approve'])->assertUnprocessable();
        $page = $this->get(route('admin.reviews.index'))->assertOk();
        $listed = $page->viewData('reviews')->firstWhere('id', $review->id);
        $snapshot = $this->getJson(route('admin.reviews.show', $review))->assertOk()->json('content_fingerprint');
        $this->assertSame($snapshot, $listed->content_fingerprint);
        $this->postJson(route('admin.reviews.approve', $review), ['content_fingerprint' => $snapshot])->assertOk();
        $this->assertSame(Review::STATUS_APPROVED, $review->fresh()->status);
        $this->postJson(route('admin.reviews.reject', $review), [
            'content_fingerprint' => $snapshot, 'reason' => 'Not suitable',
        ])->assertOk();
        $this->assertSame('Not suitable', $review->fresh()->rejection_reason);
        $this->deleteJson(route('admin.reviews.destroy', $review))->assertOk();
        $this->assertSoftDeleted($review);
    }

    public function test_bulk_requires_a_fingerprint_for_every_selected_review_and_preserves_authorization(): void
    {
        [$buyer, $admin, , $review] = $this->reviewFixture();
        $fingerprint = $review->moderationFingerprint();
        $this->actingAs($buyer)->postJson(route('admin.reviews.approve', $review), [
            'content_fingerprint' => $fingerprint,
        ])->assertForbidden();
        $this->actingAs($admin)->postJson(route('admin.reviews.bulk'), [
            'ids' => [$review->id], 'action' => 'approve', 'fingerprints' => ['unselected' => $fingerprint],
        ])->assertStatus(409);
        $this->postJson(route('admin.reviews.bulk'), [
            'ids' => [$review->id], 'action' => 'approve', 'fingerprints' => [$review->id => $fingerprint],
        ])->assertOk()->assertJson(['updated' => 1]);
        $this->postJson(route('admin.reviews.bulk'), ['ids' => [$review->id], 'action' => 'delete'])->assertOk();
        $this->assertSoftDeleted($review);
    }
}
