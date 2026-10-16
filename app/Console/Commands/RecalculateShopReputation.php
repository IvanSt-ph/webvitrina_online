<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateShopReputation extends Command
{
    protected $signature = 'shops:recalculate-reputation
        {--apply : Записать рассчитанные значения; без флага выполняется только dry-run}';

    protected $description = 'Проверяет и при явном --apply пересчитывает рейтинг, отзывы, продажи и репутацию магазинов';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $changed = 0;
        $processed = 0;

        $this->info($apply ? 'Режим APPLY: изменения будут сохранены.' : 'Режим DRY-RUN: база данных не изменяется.');

        Shop::query()->orderBy('id')->chunkById(200, function ($shops) use ($apply, &$changed, &$processed): void {
            foreach ($shops as $shop) {
                $processed++;
                $old = $this->values($shop);
                $new = $this->calculate($shop);

                if ($old === $new) {
                    continue;
                }

                $changed++;
                $this->line(sprintf(
                    'shop=%d rating %s→%s reviews %d→%d sales %d→%d reputation %s→%s',
                    $shop->id,
                    $old['rating'],
                    $new['rating'],
                    $old['reviews_count'],
                    $new['reviews_count'],
                    $old['sales_count'],
                    $new['sales_count'],
                    $old['seller_reputation'],
                    $new['seller_reputation'],
                ));

                if ($apply) {
                    DB::transaction(function () use ($shop): void {
                        $locked = Shop::query()->whereKey($shop->id)->lockForUpdate()->firstOrFail();
                        $locked->forceFill($this->calculate($locked))->save();
                    }, 3);
                }
            }
        });

        $this->info("Обработано магазинов: {$processed}; расхождений: {$changed}.");

        return self::SUCCESS;
    }

    private function calculate(Shop $shop): array
    {
        $user = User::withTrashed()->findOrFail($shop->user_id);
        $aggregate = $user->approvedProductReviews()
            ->toBase()
            ->selectRaw('COALESCE(AVG(reviews.rating), 0) as avg_rating, COUNT(*) as reviews_count')
            ->first();
        $rating = round((float) $aggregate->avg_rating, 2);
        $reviewsCount = (int) $aggregate->reviews_count;
        $salesCount = $shop->completedSalesCount();

        return [
            'rating' => number_format($rating, 2, '.', ''),
            'reviews_count' => $reviewsCount,
            'sales_count' => $salesCount,
            'seller_reputation' => Shop::reputationFor($rating, $salesCount, $reviewsCount),
        ];
    }

    private function values(Shop $shop): array
    {
        return [
            'rating' => number_format((float) $shop->rating, 2, '.', ''),
            'reviews_count' => (int) $shop->reviews_count,
            'sales_count' => (int) $shop->sales_count,
            'seller_reputation' => (string) $shop->seller_reputation,
        ];
    }
}
