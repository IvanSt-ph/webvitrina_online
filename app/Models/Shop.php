<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        static::saved(fn (): bool => self::clearRetailMediaCache());
        static::deleted(fn (): bool => self::clearRetailMediaCache());
    }

    public static function clearRetailMediaCache(): bool
    {
        Cache::forget('ads.home');
        Cache::forget('ads.category.featured');

        return true;
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
        $this->seller_reputation = self::reputationFor(
            (float) $this->rating,
            (int) $this->sales_count,
            (int) $this->reviews_count,
        );
        $this->save();
    }

    public static function reputationFor(float $rating, int $sales, int $reviewsCount): string
    {
        if ($reviewsCount === 0) {
            return 'new';
        }

        if ($rating < 3.0) {
            return 'low_rating';
        }
        if ($sales >= 80 && $rating >= 4.6) {
            return 'top';
        }
        if ($sales >= 30 && $rating >= 4.4) {
            return 'trusted';
        }
        if ($sales >= 10 && $rating >= 4.2) {
            return 'verified';
        }

        return 'new';
    }

    public function completedSalesCount(): int
    {
        return Order::query()
            ->where('seller_id', $this->user_id)
            ->where(function ($orders) {
                $orders->where(function ($pickup) {
                    $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                        ->where('status', Order::STATUS_COMPLETED)
                        ->whereNotNull('buyer_confirmed_at')
                        ->where('payment_status', Order::PAYMENT_SELLER_CONFIRMED)
                        ->whereNotNull('paid_at')
                        ->whereNotNull('completed_at');
                })->orWhere(function ($legacy) {
                    $legacy->whereNull('workflow_version')
                        ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);
                });
            })
            ->count();
    }

    public function syncSalesCount(): void
    {
        self::syncSalesCountForUser((int) $this->user_id);
    }

    public static function syncSalesCountForUser(int $userId): void
    {
        DB::transaction(function () use ($userId): void {
            // Shop is the first and only locked row. This serializes sales and
            // reputation writers before either creates a consistent-read snapshot.
            $shop = self::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$shop) {
                return;
            }

            $shop->sales_count = $shop->completedSalesCount();
            $shop->updateReputation();
        }, 3);
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
        return \App\Support\PublicImage::url($this->banner, 'images/image-placeholder.svg');
    }

    public function getCardImageCandidatesAttribute(): array
    {
        return $this->user?->avatar_candidates ?? [asset('images/avatar-placeholder.svg')];
    }

    public function getCardImageUrlAttribute(): string
    {
        return $this->user?->avatar_url ?: asset('images/avatar-placeholder.svg');
    }
}
