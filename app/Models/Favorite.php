<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    protected $fillable = ['user_id','product_id'];

    /** Товар в избранном */
    public function product()
    {
        return $this->belongsTo(Product::class)
            ->with(['category', 'city.country', 'seller']);
    }

    /** Владелец избранного */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
