<?php
// app/Models/ReviewImage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    protected $appends = ['url', 'thumb_url', 'thumb_candidates'];

    protected $fillable = ['review_id', 'path'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function getUrlAttribute(): string
    {
        return \App\Models\Product::storageImageUrl($this->path);
    }

    public function getThumbCandidatesAttribute(): array
    {
        return Product::storageThumbCandidates($this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return \App\Models\Product::storageThumbUrl($this->path);
    }
}
