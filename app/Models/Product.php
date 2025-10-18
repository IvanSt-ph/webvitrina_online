<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'price',
        'stock',
        'image',
        'description',
        'city_id',
        'gallery',
        'status',
        'address',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'gallery' => 'array',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    // 🔹 Продавец
    public function seller() { return $this->belongsTo(User::class, 'user_id'); }

    // 🔹 Отзывы
    public function reviews() { return $this->hasMany(Review::class); }

    // 🔹 Категория
    public function category() { return $this->belongsTo(Category::class); }

    // 🔹 Город
    public function city() { return $this->belongsTo(City::class); }

    // 🔹 Страна через город
    public function getCountryAttribute() { return $this->city?->country; }

    // 🔹 Избранное
    public function favorites() { return $this->hasMany(Favorite::class); }

    public function isFavoritedBy($user)
    {
        return $user && $this->favorites()->where('user_id', $user->id)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Хуки модели
    |--------------------------------------------------------------------------
    */
    protected static function boot()
    {
        parent::boot();

        // 🆔 Генерация slug
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $base = Str::slug($product->title);
                $slug = $base;
                $i = 1;
                while (self::where('slug', $slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $product->slug = $slug;
            }
        });

        // ⚙️ Удаляем только изменённое главное фото
        static::updating(function ($product) {
            if ($product->isDirty('image')) {
                $old = $product->getOriginal('image');
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        // 🧹 При полном удалении товара удаляем только связанные файлы
        static::deleting(function ($product) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            if (is_array($product->gallery)) {
                foreach ($product->gallery as $path) {
                    if ($path && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Акцессоры
    |--------------------------------------------------------------------------
    */
    public function getPriceFormattedAttribute(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' ₽';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getCountryNameAttribute(): ?string
    {
        return $this->country?->name;
    }

    public function getCityNameAttribute(): ?string
    {
        return $this->city?->name;
    }

    // 🔹 Старые slug для редиректа
    public function oldSlugs()
    {
        return $this->hasMany(ProductSlug::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes для фильтров
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByCity($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }
}
