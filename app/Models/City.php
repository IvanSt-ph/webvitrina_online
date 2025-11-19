<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'country_id',
    ];

    /* -------------------------------------------------
     | 🔹 Связи
     |--------------------------------------------------*/

    /** Страна города */
    public function country()
    {
        return $this->belongsTo(Country::class)->withDefault();
    }

    /** Товары, размещённые в этом городе */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** Адреса пользователей (доставка) */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }


    /* -------------------------------------------------
     | 🔧 Скоупы (фильтры)
     |--------------------------------------------------*/

    /** Города внутри заданной страны */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /** Сортировка по алфавиту */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /** Популярные города (если добавишь поле popularity) */
    public function scopePopular($query)
    {
        return $query->orderBy('popularity', 'desc');
    }


    /* -------------------------------------------------
     | 🖼 Аксессоры
     |--------------------------------------------------*/

    /** Полное название: "Тирасполь, ПМР" */
    public function getFullNameAttribute()
    {
        return $this->country
            ? "{$this->name}, {$this->country->name}"
            : $this->name;
    }
}
