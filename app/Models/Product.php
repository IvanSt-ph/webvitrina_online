<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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

    protected $with = [
        'category',
        'city.country',
        'seller',
    ];

    /* -------------------------------------------------
     | 🔗 СВЯЗИ
     |--------------------------------------------------*/

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withDefault();
    }

    public function city()
    {
        return $this->belongsTo(City::class)->withDefault();
    }

    public function getCountryAttribute()
    {
        return $this->city?->country;
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function isFavoritedBy($user): bool
    {
        if (!$user) return false;
        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function user()
    {
        return $this->seller();
    }

    public function stats()
    {
        return $this->hasMany(ProductStat::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function oldSlugs()
    {
        return $this->hasMany(ProductSlug::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class);
    }

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
     | 🖼 АКСЕССОРЫ ИЗОБРАЖЕНИЙ (только для отображения)
     |--------------------------------------------------*/

    public function getImageUrlAttribute(): string
    {
        if ($this->image && !empty($this->image)) {
            if (\Storage::disk('public')->exists($this->image)) {
                return asset('storage/' . $this->image);
            }
        }
        
        return asset('storage/default/no-image.png');
    }

    public function getGalleryImagesAttribute(): array
    {
        $images = [];
        
        if (!empty($this->gallery) && is_array($this->gallery)) {
            foreach ($this->gallery as $img) {
                if ($img && \Storage::disk('public')->exists($img)) {
                    $images[] = asset('storage/' . $img);
                }
            }
        }
        
        if (empty($images)) {
            $images[] = $this->image_url;
        }
        
        return $images;
    }

    public function getFirstGalleryImageAttribute(): string
    {
        return $this->gallery_images[0] ?? $this->image_url;
    }

    public function getHasGalleryAttribute(): bool
    {
        return !empty($this->gallery) && is_array($this->gallery) && count($this->gallery) > 0;
    }

    public function getAllImagesAttribute(): array
    {
        $images = array_filter([$this->image_url]);
        
        if ($this->has_gallery) {
            foreach ($this->gallery_images as $galleryImage) {
                if ($galleryImage !== $this->image_url) {
                    $images[] = $galleryImage;
                }
            }
        }
        
        return array_values($images);
    }

    public function getAllImagesRawAttribute(): array
    {
        $images = array_filter([$this->image]);
        
        if (is_array($this->gallery)) {
            $images = array_merge($images, $this->gallery);
        }
        
        return array_values(array_unique($images));
    }

    public function getImagesCountAttribute(): int
    {
        return count($this->all_images);
    }

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
     | ⚙️ BOOT - ТОЛЬКО SLUG!
     |--------------------------------------------------*/

    protected static function boot()
    {
        parent::boot();

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
    }
}