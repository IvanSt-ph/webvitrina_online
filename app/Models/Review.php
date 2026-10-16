<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Review extends Model
{
    use SoftDeletes;

    private array $forceDeletingImagePaths = [];

    // 🔹 Разрешённые поля
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'body',
        'status',
        'rejection_reason',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
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

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
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


    protected static function booted()
{
    static::saved(function ($review) {
        if ($review->product) {
            \App\Repositories\ProductRepository::clearProductCache($review->product);
        }

        cache()->forget('products.home.high_rating_recommendations');
        cache()->forget('products.home.catalog_recommendations');
    });

    static::deleting(function ($review) {
        if ($review->isForceDeleting()) {
            $review->forceDeletingImagePaths = $review->images()->pluck('path')->all();
        }
    });

    static::forceDeleted(function ($review) {
        foreach ($review->forceDeletingImagePaths as $path) {
            DB::afterCommit(function () use ($path): void {
                try {
                    app(\App\Services\ReviewImageStorage::class)
                        ->deleteIfUnreferenced($path, throwOnFailure: true);
                } catch (\Throwable $exception) {
                    \Illuminate\Support\Facades\Log::error('Review image cleanup failed; retry required', [
                        'path' => $path,
                        'phase' => 'force_delete',
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        }
    });

    static::deleted(function ($review) {
        if ($review->product) {
            \App\Repositories\ProductRepository::clearProductCache($review->product);
        }

        cache()->forget('products.home.high_rating_recommendations');
        cache()->forget('products.home.catalog_recommendations');
    });
}

}
