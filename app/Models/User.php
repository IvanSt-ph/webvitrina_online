<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\{
    Product,
    Order,
    Review,
    Shop,
    Favorite,
    UserAddress
};

use App\Notifications\VerifyEmail;
use App\Notifications\ResetPasswordNotification;
use App\Services\ImageService;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if ($user->wasChanged(['name', 'avatar'])) {
                Shop::clearRetailMediaCache();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 🔐 FILLABLE
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_set_at',
        'role',
        'seller_plan',
        'preferred_currency',
        'locale',
        'notification_preferences',
        'avatar',
        'phone',
        'phone_verified_at',
        'phone_verification_code',
        'provider',
        'provider_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🙈 HIDDEN
    |--------------------------------------------------------------------------
    */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verification_code',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🔄 CASTS
    |--------------------------------------------------------------------------
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_set_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 🎭 ROLES
    |--------------------------------------------------------------------------
    */
    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    /*
    |--------------------------------------------------------------------------
    | 📦 RELATIONS
    |--------------------------------------------------------------------------
    */
    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Product::class,
            'user_id',
            'product_id'
        );
    }

    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class)->latest();
    }

    public function followedShops()
    {
        return $this->belongsToMany(Shop::class, 'shop_followers')->withTimestamps();
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function buyerConversations()
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function sellerConversations()
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    public function sellerPlanRequests()
    {
        return $this->hasMany(SellerPlanRequest::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class)->latest();
    }

    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class)->where('is_default', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | 🖼 AVATAR
    |--------------------------------------------------------------------------
    */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            $path = ltrim(str_replace(['storage/', '/storage/'], '', $this->avatar), '/');
            $thumb = ImageService::thumbPath($path);

            if (Storage::disk('public')->exists($thumb)) {
                return Storage::url($thumb);
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::url($path);
            }
        }
        
        // Аватар по умолчанию на основе имени
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /*
    |--------------------------------------------------------------------------
    | ⭐ REVIEWS STATS
    |--------------------------------------------------------------------------
    */
    public function getReviewsAvgRatingAttribute(): float
    {
        $avg = $this->reviews()
            ->where('reviews.status', Review::STATUS_APPROVED)
            ->avg('reviews.rating');

        return $avg ? round((float) $avg, 2) : 0.00;
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()
            ->where('reviews.status', Review::STATUS_APPROVED)
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | ✉️ EMAIL VERIFICATION
    |--------------------------------------------------------------------------
    */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail());
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /*
    |--------------------------------------------------------------------------
    | 📱 PHONE VERIFICATION
    |--------------------------------------------------------------------------
    */

    // Проверен ли телефон
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    // Нужно ли подтверждать телефон
    public function phoneNeedsVerification(): bool
    {
        return !$this->hasVerifiedPhone() && !is_null($this->phone);
    }

    public function hasLocalPassword(): bool
    {
        return !is_null($this->password_set_at);
    }

    // Отметить телефон подтверждённым
    public function markPhoneAsVerified(): void
    {
        $this->forceFill([
            'phone_verified_at' => now(),
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | 🔎 SCOPES
    |--------------------------------------------------------------------------
    */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /*
    |--------------------------------------------------------------------------
    | ✍️ MUTATORS
    |--------------------------------------------------------------------------
    */

    // Нормализация телефона
    public function setPhoneAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['phone'] = null;
            return;
        }

        $this->attributes['phone'] = preg_replace('/[^0-9+]/', '', $value);
    }
}
