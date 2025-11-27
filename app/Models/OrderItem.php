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

    // Всегда подгружаем товар (даже soft-deleted)
    protected $with = ['product'];

    /** Заказ */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** Товар (даже если он soft-deleted) */
        public function product()
        {
            return $this->belongsTo(Product::class)->withTrashed()->with(['category', 'seller']);
        }


    /** Считаем total если вдруг пусто */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }
}
