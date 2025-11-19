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
        'payment_method',
        'delivery_method',
        'paid_at',
        'address_id',
    ];

    /** Загрузим частые связи ОДНИМ SQL */
    protected $with = [
        'items.product',
        'user',
        'seller',
        'address',
    ];

    /* -------------------------------------------------
     | 🔗 СВЯЗИ
     |--------------------------------------------------*/

    /** Позиции заказа */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Адрес доставки */
    public function address()
    {
        return $this->belongsTo(UserAddress::class)->withDefault();
    }

    /** Покупатель */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /** Продавец */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id')->withDefault();
    }

    /** Товары заказа */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
                    ->withPivot(['quantity', 'price'])
                    ->withTimestamps();
    }

    /* -------------------------------------------------
     | 🏷 Аксессоры
     |--------------------------------------------------*/

    public function getStatusRuAttribute()
    {
        return match($this->status) {
            'pending'   => 'Ожидает оплаты',
            'paid'      => 'Оплачен',
            'shipped'   => 'В пути',
            'delivered' => 'Доставлен',
            'canceled'  => 'Отменён',
            default     => $this->status,
        };
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->total_price, 2, ',', ' ') . ' ' . $this->currency;
    }

    /* -------------------------------------------------
     | 🔍 Скоупы
     |--------------------------------------------------*/

    /** Заказы конкретного продавца */
    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }
}
