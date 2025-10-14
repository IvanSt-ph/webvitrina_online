<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    // 🔹 Разрешённые поля
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'body',
        'status',
    ];

    // 🔹 Константы статусов
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // 🔹 Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }

    // 🔹 Удобный “человеческий” статус
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING  => '⏳ На модерации',
            self::STATUS_APPROVED => '✅ Одобрен',
            self::STATUS_REJECTED => '🚫 Отклонён',
            default               => '—',
        };
    }
}
