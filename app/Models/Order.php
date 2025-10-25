<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'seller_id',
        'number',
        'status',
        'total_price',
        'currency',
        'delivery_address',
        'payment_method',
        'delivery_method',
        'paid_at',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
