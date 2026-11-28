<?php

namespace App\Support;

class CategorySvg
{
    /** @var array<string, true>|null */
    private static ?array $verifiedSlugs = null;

    public static function urlForSlug(?string $slug): ?string
    {
        if (!config('category_svg.enabled', false)
            || !is_string($slug)
            || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug)) {
            return null;
        }

        self::$verifiedSlugs ??= array_fill_keys(config('category_svg.slugs', []), true);

        if (!isset(self::$verifiedSlugs[$slug])) {
            return null;
        }

        return asset('images/categories/svg/' . rawurlencode($slug) . '.svg');
    }
}
