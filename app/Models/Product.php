<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\SoftDeletes;




class Product extends Model
{

    // удаление файлов при уничтожении товара
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



    


    /** Просмотры */
    // public function views()
    // {
    //     return $this->hasMany(ProductStat::class);
    // }
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
     | 🖼 АКСЕССОРЫ
     |--------------------------------------------------*/

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
