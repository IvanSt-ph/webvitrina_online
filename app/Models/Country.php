<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /* -------------------------------------------------
     | 🔹 Связи
     |--------------------------------------------------*/

    /** Все города внутри страны */
    public function cities()
    {
        return $this->hasMany(City::class)->orderBy('name');
    }

    /** Адреса пользователей, которые выбирают эту страну */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }


    /* -------------------------------------------------
     | 🔹 Скоупы (фильтры)
     |--------------------------------------------------*/

    /** Только страны, где есть хотя бы один город */
    public function scopeWithCities($query)
    {
        return $query->has('cities');
    }

    /** Сортировка по алфавиту */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }


    /* -------------------------------------------------
     | 🔹 Аксессоры
     |--------------------------------------------------*/

    /** Количество городов внутри страны */
    public function getCitiesCountAttribute()
    {
        return $this->cities()->count();
    }

    /** Простое короткое имя страны (если понадобится ISO-код — добавим) */
    public function getLabelAttribute()
    {
        return $this->name;
    }
}
