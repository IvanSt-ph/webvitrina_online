<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country',
        'city',
        'street',
        'house',
        'entrance',
        'apartment',
        'postal_code',
        'comment',
        'is_default',
    ];

    // Связь с пользователем
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Метод для получения полного адреса в виде строки
    public function getFullAttribute()
    {
        $parts = [];
        
        if ($this->country) $parts[] = $this->country;
        if ($this->city) $parts[] = $this->city;
        if ($this->street) $parts[] = $this->street;
        if ($this->house) $parts[] = 'д. ' . $this->house;
        if ($this->apartment) $parts[] = 'кв. ' . $this->apartment;
        if ($this->entrance) $parts[] = 'подъезд ' . $this->entrance;
        if ($this->postal_code) $parts[] = '(' . $this->postal_code . ')';
        
        return implode(', ', $parts);
    }
}