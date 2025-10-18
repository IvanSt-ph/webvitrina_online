<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = ['name','type','options'];
    protected $casts = ['options' => 'array'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'attribute_category');
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
