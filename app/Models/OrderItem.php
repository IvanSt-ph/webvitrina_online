<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'price',
        'quantity',
        'total',
    ];

    protected $with = ['product'];

    /** Заказ */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** Товар */
    public function product()
    {
        return $this->belongsTo(Product::class)->with(['category', 'seller']);
    }

    /** Считаем total если вдруг пусто */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }
}
