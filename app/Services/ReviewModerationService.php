<?php

namespace App\Services;

use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReviewModerationService
{
    public function moderate(array $ids, array $fingerprints, string $status, int $moderatorId, ?string $reason = null): Collection
    {
        return DB::transaction(function () use ($ids, $fingerprints, $status, $moderatorId, $reason) {
            // All writers lock the review before changing content or its images.
            // Bulk operations acquire review locks in one deterministic order.
            $reviews = Review::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            abort_if($reviews->count() !== count(array_unique($ids)), 409, 'Список отзывов изменился. Обновите страницу.');

            foreach ($reviews as $review) {
                // A locking read avoids a pre-existing REPEATABLE READ snapshot.
                $review->setRelation('images', $review->images()->orderBy('id')->lockForUpdate()->get());
                $expected = $fingerprints[$review->id] ?? null;
                abort_unless(is_string($expected) && hash_equals($review->moderationFingerprint(), $expected),
                    409, 'Отзыв изменился. Обновите страницу и проверьте его заново.');
            }

            // Validate the entire batch before any decision is written.
            foreach ($reviews as $review) {
                $review->update([
                    'status' => $status,
                    'rejection_reason' => $status === Review::STATUS_REJECTED ? $reason : null,
                    'moderated_by' => $moderatorId,
                    'moderated_at' => now(),
                ]);
            }

            return $reviews;
        });
    }
}
