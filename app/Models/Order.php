<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserAddress;
use App\Models\User;
use App\Models\Product;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // ✅ Добавляем связь с товарами
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot('quantity', 'price')
                    ->withTimestamps();
    }

    public function getStatusRuAttribute()
{
    return match($this->status) {
        'pending'   => 'Ожидает оплаты',
        'paid'      => 'Оплачен',
        'shipped'   => 'В пути',
        'delivered' => 'Доставлен',
        'canceled'  => 'Отменён',
        default     => ucfirst($this->status),
    };
}

}
