<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\ProductService;

class PurgeOldProducts extends Command
{
    protected $signature = 'products:purge-old';
    protected $description = 'Удаляет товары, удалённые более 90 дней назад';

    public function handle()
    {
        $days = 90;

        $count = Product::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->count();

        if ($count === 0) {
            $this->info("Нет товаров старше $days дней.");
            return Command::SUCCESS;
        }

        $deletedBefore = now()->subDays($days);
        $count = 0;
        Product::onlyTrashed()
            ->where('deleted_at', '<', $deletedBefore)
            ->chunkById(200, function ($products) use ($deletedBefore, &$count) {
                foreach ($products as $product) {
                    $count += (int) app(ProductService::class)->purge($product, $deletedBefore);
                }
            });

        $this->info("Удалено товаров: $count");
        return Command::SUCCESS;
    }
}
