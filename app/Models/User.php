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

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | 🔐 FILLABLE
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
    ];

    /*
    |--------------------------------------------------------------------------
    | 🙈 HIDDEN
    |--------------------------------------------------------------------------
    */
    protected $hidden = [
        'password',
        'remember_token',
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

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
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
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        return asset('images/default-avatar.png');
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

    // Нормализация телефона (ВАЖНО для Twilio и уникальности)
    public function setPhoneAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['phone'] = null;
            return;
        }

        $this->attributes['phone'] = preg_replace('/[^0-9+]/', '', $value);
    }
}
