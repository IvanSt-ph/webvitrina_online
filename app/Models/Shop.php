<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'phone',
        'city',
        'banner',
    ];

    // 🔹 Связь: магазин принадлежит пользователю
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Удобный URL баннера
    public function getBannerUrlAttribute(): string
    {
        return $this->banner
            ? Storage::url($this->banner)
            : asset('images/default-shop-banner.jpg');
    }
}
