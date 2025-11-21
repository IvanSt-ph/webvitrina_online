<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

protected $fillable = [
    'name',
    'email',
    'password',
    'role',
    'avatar',
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

    // 🔹 Удобный аксессор для получения URL аватара
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar); // вернет /storage/avatars/xxx.jpg
        }
        return asset('images/default-avatar.png'); // дефолтная картинка
    }


// 🔹 Средний рейтинг продавца
public function getReviewsAvgRatingAttribute(): float
{
    // Берём только одобренные отзывы
    $avg = $this->reviews()
        ->where('reviews.status', \App\Models\Review::STATUS_APPROVED) // ✅ уточняем таблицу
        ->avg('reviews.rating'); // ✅ тоже уточняем таблицу

    return $avg ? round((float)$avg, 2) : 0.00;
}

public function getReviewsCountAttribute(): int
{
    return $this->reviews()
        ->where('reviews.status', \App\Models\Review::STATUS_APPROVED) // ✅ уточняем таблицу
        ->count();
}


public function shop()
{
    return $this->hasOne(Shop::class);
}

public function favorites()
{
    return $this->hasMany(\App\Models\Favorite::class)->latest();
}

// 🔹 Адреса пользователя
public function addresses()
{
    return $this->hasMany(UserAddress::class);
}

// 🔹 Основной адрес (is_default = 1)
public function defaultAddress()
{
    return $this->hasOne(UserAddress::class)->where('is_default', 1);
}


    
}
