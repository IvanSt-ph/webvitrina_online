<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
    ];

    // ❌ НЕ ДОБАВЛЯЕМ is_phone_verified в fillable!
    // Это вычисляемое поле, защищённое от массового присваивания

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'phone_verification_expires_at' => 'datetime',
    ];

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
        return $this->hasMany(Product::class);
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