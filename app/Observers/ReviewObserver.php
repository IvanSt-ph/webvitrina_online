<?php

namespace App\Observers;

use App\Models\Review;

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

        if (!$product) {
            return;
        }

        // Владелец товара (User)
        $user = $product->user;

        // Магазин продавца
        $shop = $user->shop ?? null;

        if (!$shop) {
            return;
        }

        // Пересчитываем рейтинг по всем товарам этого магазина
        $agg = Review::query()
            ->whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->selectRaw('COALESCE(AVG(rating),0) as avg_rating, COUNT(*) as cnt')
            ->first();

        $shop->rating = round((float)$agg->avg_rating, 2);
        $shop->reviews_count = (int)$agg->cnt;
        $shop->save();
    }
}
