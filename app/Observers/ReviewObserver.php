<?php

// app/Observers/ReviewObserver.php
namespace App\Observers;

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewObserver
{
    public function created(Review $review): void   { $this->recalcSeller($review); }
    public function updated(Review $review): void   { $this->recalcSeller($review); }
    public function deleted(Review $review): void   { $this->recalcSeller($review); }
    public function restored(Review $review): void  { $this->recalcSeller($review); }
    public function forceDeleted(Review $review): void { $this->recalcSeller($review); }

    protected function recalcSeller(Review $review): void
    {
        $product = $review->product;
        if (!$product || !$product->seller) return;

        $seller = $product->seller;

        // Пересчёт средней оценки и количества отзывов по всем товарам этого продавца
        $agg = \App\Models\Review::query()
            ->whereHas('product', fn($q) => $q->where('user_id', $seller->id))
            ->selectRaw('COALESCE(AVG(rating),0) as avg_rating, COUNT(*) as cnt')
            ->first();

        $seller->rating = round((float)$agg->avg_rating, 2);
        $seller->reviews_count = (int)$agg->cnt;
        $seller->save();
    }
}
