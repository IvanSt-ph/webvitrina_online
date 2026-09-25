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
        'source_price',
        'source_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'source_price' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
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
