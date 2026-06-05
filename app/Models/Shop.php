<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'phone',
        'phone_verified_at',
        'phone_verification_code',
        'phone_verification_expires_at',
        'city',
        'banner',
        'facebook',
        'instagram',
        'telegram',
        'whatsapp',
        'slug',
    ];

    // ❌ НЕ ДОБАВЛЯЕМ is_phone_verified в fillable!
    // Это вычисляемое поле, защищённое от массового присваивания

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'phone_verification_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shop $shop): void {
            if (blank($shop->slug)) {
                $shop->slug = self::generateUniqueSlug($shop);
            }
        });
    }

    public static function generateUniqueSlug(Shop $shop): string
    {
        $base = Str::slug($shop->name ?? '') ?: 'shop-' . ($shop->user_id ?: Str::lower(Str::random(8)));
        $slug = $base;
        $counter = 1;

        while (self::where('slug', $slug)
            ->when($shop->exists, fn ($query) => $query->whereKeyNot($shop->getKey()))
            ->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    // ✅ Аксессор для удобства (только для чтения)
    public function getIsPhoneVerifiedAttribute(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    // ✅ Метод для безопасной верификации
    public function verifyPhone(string $code): bool
    {
        // Проверяем код и срок действия
        if ($this->phone_verification_code !== $code) {
            return false;
        }

        if ($this->phone_verification_expires_at && $this->phone_verification_expires_at->isPast()) {
            return false;
        }

        $this->update([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
        ]);

        return true;
    }

    // ✅ Метод для отправки нового кода
    public function generateVerificationCode(): string
    {
        $code = (string) random_int(100000, 999999);
        
        $this->update([
            'phone_verification_code' => $code,
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verified_at' => null, // сбрасываем подтверждение
        ]);

        return $code;
    }


    // ✅ Метод для обновления репутации продавца
    public function updateReputation(): void
{
    $rating = $this->rating;
    $sales = $this->sales_count;

    if ($rating < 3.0) {
        $this->seller_reputation = 'low_rating';
    } elseif ($sales >= 80 && $rating >= 4.6) {
        $this->seller_reputation = 'top';
    } elseif ($sales >= 30 && $rating >= 4.4) {
        $this->seller_reputation = 'trusted';
    } elseif ($sales >= 10 && $rating >= 4.2) {
        $this->seller_reputation = 'verified';
    } else {
        $this->seller_reputation = 'new';
    }

    $this->save();
}

// / ✅ Метод для пересчёта продаж и обновления репутации 
public function incrementSales(int $amount = 1): void
{
    $this->sales_count += $amount;
    $this->save();

    // Пересчёт репутации
    $this->updateReputation();
}

// ✅ Удобный аксессор для отображения
public function getReputationLabelAttribute(): string
{
    return match($this->seller_reputation) {
        'top' => 'Платиновый уровень',
        'trusted' => 'Золотой уровень',
        'verified' => 'Серебряный уровень',
        'new' => 'Бронзовый уровень',
        'low_rating' => 'Требует внимания',
        default => 'Уровень продавца',
    };
}

public function getReputationDescriptionAttribute(): string
{
    return match($this->seller_reputation) {
        'top' => 'Высший уровень доверия и качества',
        'trusted' => 'Много продаж, высокий рейтинг',
        'verified' => 'Проверенный временем, хорошие отзывы',
        'new' => 'Надёжный базовый уровень',
        'low_rating' => 'Рейтинг ниже обычного, проверьте отзывы перед заказом',
        default => 'Публичный уровень продавца',
    };
}

    // ✅ Проверка, нужно ли подтверждение
    public function needsPhoneVerification(): bool
    {
        return !$this->is_phone_verified && !is_null($this->phone);
    }

    // ✅ Проверка, просрочен ли код
    public function isVerificationCodeExpired(): bool
    {
        return $this->phone_verification_expires_at && $this->phone_verification_expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'user_id', 'user_id');
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'shop_followers')->withTimestamps();
    }

    public function isFollowedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->followers()->whereKey($user->id)->exists();
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getBannerUrlAttribute(): string
    {
        return $this->banner
            ? Storage::url($this->banner)
            : asset('images/default-shop-banner.jpg');
    }
}
