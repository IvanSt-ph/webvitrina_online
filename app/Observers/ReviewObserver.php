<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\RecalculateSellerReputation;

class ReviewObserver
{
    public function created(Review $review): void   { $this->recalcSeller($review); }
    public function updated(Review $review): void   { $this->recalcSeller($review); }
    public function deleted(Review $review): void   { $this->recalcSeller($review); }
    public function restored(Review $review): void  { $this->recalcSeller($review); }
    public function forceDeleted(Review $review): void { $this->recalcSeller($review); }

    protected function recalcSeller(Review $review): void
    {
        $productId = (int) $review->product_id;

        DB::afterCommit(function () use ($productId): void {
            $userId = null;

            try {
                DB::transaction(function () use ($productId, &$userId): void {
                    // Fresh post-commit transaction: product -> shop is the only
                    // lock order used by review-driven recalculations.
                    $userId = Product::withTrashed()
                        ->whereKey($productId)
                        ->sharedLock()
                        ->value('user_id');

                    if ($userId !== null) {
                        $this->recalculateSellerByUserId((int) $userId);
                    }
                }, 3);
            } catch (\Throwable $exception) {
                $this->reportRecalculationFailure($userId ? (int) $userId : null, $productId, $exception);
            }
        });
    }

    public function scheduleSellerRecalculation(int $userId): void
    {
        DB::afterCommit(function () use ($userId): void {
            try {
                $this->recalculateSellerByUserId($userId);
            } catch (\Throwable $exception) {
                $this->reportRecalculationFailure($userId, null, $exception);
            }
        });
    }

    public function recalculateSellerByUserId(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            // One seller row serializes competing moderation recalculations.
            // The final writer always recomputes the complete approved set.
            $shop = Shop::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$shop) {
                return;
            }

            $user = User::withTrashed()->find($userId);

            if (!$user) {
                return;
            }

            $agg = $user->approvedProductReviews()
                ->toBase()
                ->selectRaw('COALESCE(AVG(reviews.rating), 0) as avg_rating, COUNT(*) as cnt')
                ->first();

            $shop->rating = round((float) ($agg->avg_rating ?? 0), 2);
            $shop->reviews_count = (int) ($agg->cnt ?? 0);
            $shop->updateReputation();
        }, 3);
    }

    private function reportRecalculationFailure(?int $userId, ?int $productId, \Throwable $exception): void
    {
        Log::error('Seller reputation recalculation failed after commit.', [
            'user_id' => $userId,
            'product_id' => $productId,
            'error' => $exception->getMessage(),
            'recovery' => 'shops:recalculate-reputation --apply',
        ]);

        if (!$userId) {
            return;
        }

        try {
            RecalculateSellerReputation::dispatch($userId);
        } catch (\Throwable $dispatchException) {
            Log::critical('Seller reputation recovery job could not be dispatched.', [
                'user_id' => $userId,
                'error' => $dispatchException->getMessage(),
                'recovery' => 'shops:recalculate-reputation --apply',
            ]);
        }
    }
}
