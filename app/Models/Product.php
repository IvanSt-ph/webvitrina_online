<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id','category_id','title','slug','sku','price','stock','image',
        'description','city_id','gallery','status','address','latitude','longitude',
        'currency_base','price_prb','price_mdl','price_uah',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_prb' => 'decimal:2',
        'price_mdl' => 'decimal:2',
        'price_uah' => 'decimal:2',
        'gallery' => 'array',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    /** 
     * 🔥 Автозагрузка связей для ускорения витрины 
     * (чтобы не было N+1)
     */
    protected $with = [
        'category',
        'city.country',
        'seller',
    ];

    /* -------------------------------------------------
     | 🔗 СВЯЗИ
     |--------------------------------------------------*/

    /** Продавец */
    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    /** Отзывы */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** Категория */
    public function category()
    {
        return $this->belongsTo(Category::class)->withDefault();
    }

    /** Город */
    public function city()
    {
        return $this->belongsTo(City::class)->withDefault();
    }

    /** Страна через город */
    public function getCountryAttribute()
    {
        return $this->city?->country;
    }

    /** Избранное */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function isFavoritedBy($user): bool
    {
        if (!$user) return false;

        // Быстрое существование, без загрузки модели
        return $this->favorites()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function user()
    {
        return $this->seller();
    }

    /** Статистика просмотров */
    public function stats()
    {
        return $this->hasMany(ProductStat::class);
    }

    /** Позиции корзины */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /** Позиции в заказах */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Старые slug-и */
    public function oldSlugs()
    {
        return $this->hasMany(ProductSlug::class);
    }

    /** Атрибуты товара */
    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    /** Конкретные значения атрибутов */
    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }

    /** Заказы */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id')
            ->withPivot(['quantity', 'price'])
            ->withTimestamps();
    }

    /* -------------------------------------------------
     | 💰 ВАЛЮТНЫЕ МЕТОДЫ
     |--------------------------------------------------*/

    public static function currencySymbol(string $code): string
    {
        return match (strtoupper($code)) {
            'PRB' => '₽ ПМР',
            'MDL' => 'L',
            'UAH' => '₴',
            default => $code,
        };
    }

    public function getPriceForCurrentCurrencyAttribute(): array
    {
        $code = session('currency', $this->currency_base ?: 'MDL');

        $map = [
            'PRB' => $this->price_prb,
            'MDL' => $this->price_mdl,
            'UAH' => $this->price_uah,
        ];

        $amount = $map[$code] ?? $this->price;

        return [
            'amount' => (float) $amount,
            'code'   => $code,
            'symbol' => self::currencySymbol($code),
        ];
    }

    public function getPriceFormattedAttribute(): string
    {
        $code = $this->currency_base ?: 'MDL';
        $symbol = self::currencySymbol($code);

        return number_format($this->price, 2, ',', ' ') . ' ' . $symbol;
    }

    /* -------------------------------------------------
     | 🖼 АКСЕССОРЫ ИЗОБРАЖЕНИЙ
     |--------------------------------------------------*/

/**
 * URL основной картинки с fallback
 */
public function getImageUrlAttribute(): string
{
    // Если есть изображение в базе
    if ($this->image && !empty($this->image)) {
        // Проверяем, что файл существует
        if (Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }
    }
    
    // Возвращаем встроенный SVG (всегда работает)
    return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23f3f4f6"/%3E%3Ccircle cx="100" cy="100" r="40" fill="none" stroke="%239ca3af" stroke-width="2"/%3E%3Cpath d="M70 70L130 130M130 70L70 130" stroke="%239ca3af" stroke-width="2"/%3E%3C/svg%3E';
}

/**
 * URL-ы галереи с fallback
 */
public function getGalleryImagesAttribute(): array
{
    $images = [];
    
    if (!empty($this->gallery) && is_array($this->gallery)) {
        foreach ($this->gallery as $img) {
            if ($img && Storage::disk('public')->exists($img)) {
                $images[] = asset('storage/' . $img);
            }
        }
    }
    
    // Если нет изображений в галерее
    if (empty($images)) {
        $images[] = $this->image_url; // используем тот же fallback
    }
    
    return $images;
}

    /**
     * Первое изображение из галереи (удобно для превью)
     */
    public function getFirstGalleryImageAttribute(): string
    {
        return $this->gallery_images[0] ?? $this->image_url;
    }

    /**
     * Проверка наличия галереи
     */
    public function getHasGalleryAttribute(): bool
    {
        return !empty($this->gallery) && is_array($this->gallery) && count($this->gallery) > 0;
    }

    /**
     * Все изображения товара (основное + галерея) для слайдеров
     * Использует array_filter для автоматического удаления null
     */
    public function getAllImagesAttribute(): array
    {
        // Основное изображение (array_filter уберет null если картинки нет)
        $images = array_filter([$this->image_url]);
        
        // Добавляем галерею
        if ($this->has_gallery) {
            foreach ($this->gallery_images as $galleryImage) {
                // Проверяем, чтобы не дублировать основное
                if ($galleryImage !== $this->image_url) {
                    $images[] = $galleryImage;
                }
            }
        }
        
        // Переиндексируем массив и возвращаем
        return array_values($images);
    }

    /**
     * Все изображения с原始 путями (без asset) для админки
     */
    public function getAllImagesRawAttribute(): array
    {
        $images = array_filter([$this->image]);
        
        if (is_array($this->gallery)) {
            $images = array_merge($images, $this->gallery);
        }
        
        return array_values(array_unique($images));
    }

    /**
     * Количество изображений
     */
    public function getImagesCountAttribute(): int
    {
        return count($this->all_images);
    }

    /**
     * Есть ли несколько изображений
     */
    public function getHasMultipleImagesAttribute(): bool
    {
        return $this->images_count > 1;
    }

    public function getCountryNameAttribute(): ?string
    {
        return $this->country?->name;
    }

    public function getCityNameAttribute(): ?string
    {
        return $this->city?->name;
    }

    /* -------------------------------------------------
     | 🔍 СКОУПЫ
     |--------------------------------------------------*/

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeDraft($q)
    {
        return $q->where('status', 'draft');
    }

    public function scopeByCategory($q, $id)
    {
        return $q->where('category_id', $id);
    }

    public function scopeByCity($q, $id)
    {
        return $q->where('city_id', $id);
    }

    /* -------------------------------------------------
     | ⚙️ SLUG + ОЧИСТКА ФАЙЛОВ
     |--------------------------------------------------*/

    protected static function boot()
    {
        parent::boot();

        /** Генерация slug */
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

        /** Удаление старой картинки при замене */
        static::updating(function ($product) {
            if ($product->isDirty('image')) {
                $old = $product->getOriginal('image');
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        /** Удаление файлов при уничтожении товара */
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
}