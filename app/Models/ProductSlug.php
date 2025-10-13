<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSlug extends Model
{
    protected $fillable = ['product_id', 'slug'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
