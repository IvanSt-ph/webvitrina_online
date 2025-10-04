<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'body',
    ];

    // 🔹 Кто оставил отзыв
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Какому товару отзыв
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 🔹 Фото, прикреплённые к отзыву
    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }
}
