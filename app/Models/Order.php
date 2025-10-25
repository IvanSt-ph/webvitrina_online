<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; // ✅ вот эта строка важна
use App\Models\UserAddress;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'seller_id',
        'number',
        'status',
        'total_price',
        'currency',
          'payment_method',
        'delivery_method',
        'paid_at',
        'address_id',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class);
    }
}
