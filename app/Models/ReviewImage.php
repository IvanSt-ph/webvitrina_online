<?php
// app/Models/ReviewImage.php
namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Model;

class ReviewImage extends Model
{
    protected $fillable = ['review_id', 'path'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getThumbUrlAttribute(): string
    {
        return asset('storage/' . ImageService::thumbPath($this->path));
    }
}
