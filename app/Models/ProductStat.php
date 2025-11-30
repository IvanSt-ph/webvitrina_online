<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // ----- Увеличение просмотров -----
    public static function addView($productId)
    {
        $today = date('Y-m-d');

        return self::query()
            ->updateOrCreate(
                ['product_id' => $productId, 'date' => $today],
                ['views' => \DB::raw('views + 1')]
            );
    }

    // ----- Избранное -----
    public static function addFavorite($productId)
    {
        $today = date('Y-m-d');

        return self::query()
            ->updateOrCreate(
                ['product_id' => $productId, 'date' => $today],
                ['favorites' => \DB::raw('favorites + 1')]
            );
    }

    // ----- Корзина -----
    public static function addCart($productId)
    {
        $today = date('Y-m-d');

        return self::query()
            ->updateOrCreate(
                ['product_id' => $productId, 'date' => $today],
                ['carts' => \DB::raw('carts + 1')]
            );
    }
}
