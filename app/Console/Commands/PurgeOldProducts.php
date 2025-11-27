<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

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

        Product::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->forceDelete();

        $this->info("Удалено товаров: $count");
        return Command::SUCCESS;
    }
}
