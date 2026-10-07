<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductStat extends Model
{
    protected $table = 'product_stats';

    protected $fillable = [
        'product_id',
        'date',
        'views',
        'favorites',
        'carts'
    ];

    public $timestamps = false;

    public static function addView(int $productId): int
    {
        return self::incrementDailyCounter($productId, 'views');
    }

    public static function addFavorite(int $productId): int
    {
        return self::incrementDailyCounter($productId, 'favorites');
    }

    public static function addCart(int $productId): int
    {
        return self::incrementDailyCounter($productId, 'carts');
    }

    private static function incrementDailyCounter(int $productId, string $counter): int
    {
        return self::query()->upsert(
            [[
                'product_id' => $productId,
                'date' => today()->toDateString(),
                'views' => $counter === 'views' ? 1 : 0,
                'favorites' => $counter === 'favorites' ? 1 : 0,
                'carts' => $counter === 'carts' ? 1 : 0,
            ]],
            ['product_id', 'date'],
            [$counter => DB::raw($counter.' + 1')]
        );
    }
}
