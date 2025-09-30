<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 🔹 Проверки ролей
    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    // 🔹 Все товары, добавленные продавцом
    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    // 🔹 Заказы покупателя
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // 🔹 Все отзывы ко всем товарам продавца
    public function reviews()
    {
        return $this->hasManyThrough(Review::class, Product::class, 'user_id', 'product_id');
    }
}
