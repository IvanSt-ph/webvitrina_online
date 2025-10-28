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

    /* -------------------------------------------------
     | 💰 Валютные методы
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
     | 🧩 Связи
     |--------------------------------------------------*/
    public function seller() { return $this->belongsTo(User::class, 'user_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function getCountryAttribute() { return $this->city?->country; }
    public function favorites() { return $this->hasMany(Favorite::class); }
    // --- 🔹 Реальные связи для аналитики ---
public function views()
{
    return $this->hasMany(\App\Models\ProductStat::class);
}

public function cartItems()
{
    return $this->hasMany(\App\Models\CartItem::class);
}

    
    /* -------------------------------------------------
 | 🕓 Старые slug-и (редиректы)
 |--------------------------------------------------*/
public function oldSlugs()
{
    return $this->hasMany(\App\Models\ProductSlug::class);
}


    public function isFavoritedBy($user)
    {
        return $user && $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function attributes()
    {
        return $this->belongsToMany(\App\Models\Attribute::class, 'attribute_values')
                    ->withPivot('value')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->belongsToMany(\App\Models\Order::class, 'order_items', 'product_id', 'order_id')
                    ->withPivot(['quantity', 'price'])
                    ->withTimestamps();
    }

    /* -------------------------------------------------
     | ⚙️ Хуки и служебные методы
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

        static::updating(function ($product) {
            if ($product->isDirty('image')) {
                $old = $product->getOriginal('image');
                if ($old && Storage::disk('public')->exists($old)) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

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

    
    /* -------------------------------------------------
     | 🌍 Доп. атрибуты
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
     | 🔍 Скоупы
     |--------------------------------------------------*/
    public function scopeActive($q)  { return $q->where('status', 'active'); }
    public function scopeDraft($q)   { return $q->where('status', 'draft'); }
    public function scopeByCategory($q, $id) { return $q->where('category_id', $id); }
    public function scopeByCity($q, $id)     { return $q->where('city_id', $id); }
}
