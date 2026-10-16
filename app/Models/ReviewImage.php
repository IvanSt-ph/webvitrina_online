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
        return $this->mediaUrl('medium');
    }

    public function getThumbCandidatesAttribute(): array
    {
        return array_values(array_unique([
            $this->mediaUrl('thumb'),
            $this->mediaUrl('medium'),
            asset('images/image-placeholder.svg'),
        ]));
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->mediaUrl('thumb');
    }

    private function mediaUrl(string $variant): string
    {
        if (!$this->exists || !$this->getKey()) {
            $legacy = \App\Support\PublicImage::path($this->path);
            if ($legacy === null) {
                return asset('images/image-placeholder.svg');
            }

            return \App\Support\PublicImage::url(
                $variant === 'thumb' ? \App\Services\ImageService::thumbPath($legacy) : $legacy
            ) ?? asset('images/image-placeholder.svg');
        }

        return route('review-images.show', [
            'reviewImage' => $this->getKey(),
            'variant' => $variant,
        ]);
    }
}
