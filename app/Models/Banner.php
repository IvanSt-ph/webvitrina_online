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
    public function imageUrl(string $device = 'desktop'): string
    {
        return $this->imageCandidates($device)[0];
    }

    public function imageCandidates(string $device = 'desktop'): array
    {
        $preferred = in_array($device, ['desktop', 'tablet', 'mobile'], true) ? $this->{'image_' . $device} : null;
        return \App\Support\PublicImage::candidatesFromPaths([
            $preferred, $this->image_desktop, $this->image_tablet, $this->image_mobile, $this->image,
        ]);
    }

    /** Single-banner editor only: at most four exact file checks, never a listing. */
    public function cropSources(): array
    {
        $available = [];
        foreach (array_unique(array_filter([$this->image_desktop, $this->image_tablet, $this->image_mobile, $this->image])) as $raw) {
            $path = \App\Support\PublicImage::path($raw);
            if ($path !== null && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $available[$raw] = \App\Support\PublicImage::url($raw);
            }
        }

        return [
            'main' => array_values($available)[0] ?? null,
            'mobile' => $available[$this->image_mobile ?? ''] ?? array_values($available)[0] ?? null,
        ];
    }
}
