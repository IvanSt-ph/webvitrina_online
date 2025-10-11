<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image',
        'image_desktop',
        'image_tablet',
        'image_mobile',
        'link',
        'sort_order',
        'active',
    ];
}
