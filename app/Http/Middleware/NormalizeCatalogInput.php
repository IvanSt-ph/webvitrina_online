<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class NormalizeCatalogInput
{
    public const MAX_PAGE = 10000;
    public const MAX_PER_PAGE = 100;
    public const SORTS = ['popular', 'new', 'price_asc', 'price_desc', 'rating', 'benefit'];

    public function handle(Request $request, Closure $next)
    {
        foreach (['slug', 'identifier'] as $parameter) {
            $value = $request->route($parameter);
            if ($value !== null) {
                abort_unless(is_string($value) && mb_strlen($value) <= 255
                    && preg_match('/^[\pL\pN_-]+$/uD', $value), 404);
            }
        }
        $input = $request->query();
        $clean = [];
        // These endpoints share a layout which also renders the search field.
        if (isset($input['q']) && is_string($input['q'])) {
            $clean['q'] = mb_substr(trim($input['q']), 0, 100);
        }
        foreach (['page' => self::MAX_PAGE, 'per_page' => self::MAX_PER_PAGE] as $key => $max) {
            if (isset($input[$key])) {
                $clean[$key] = self::positiveInteger($input[$key], $max) ?? ($key === 'page' ? 1 : 20);
            }
        }
        if (isset($input['sort']) && in_array($input['sort'], self::SORTS, true)) {
            $clean['sort'] = $input['sort'];
        }
        if (isset($input['filter']) && in_array($input['filter'], ['all', 'new', 'sale', 'hit'], true)) {
            $clean['filter'] = $input['filter'];
        }
        foreach (['category_id' => Category::class, 'user_id' => User::class] as $key => $model) {
            $id = self::positiveInteger($input[$key] ?? null);
            if ($id !== null && $model::whereKey($id)->exists()) {
                $clean[$key] = $id;
            }
        }
        // RememberLocation has already checked existence and country/city consistency.
        foreach (['country_id', 'city_id', 'chat', 'admin_chat'] as $key) {
            if (($id = self::positiveInteger($input[$key] ?? null)) !== null) {
                $clean[$key] = $id;
            }
        }
        if ($request->routeIs('category.show', 'category.ajax')) {
            // Category-specific validation runs after resolving the category.
            $filters = $input['filters'] ?? [];
            $clean['filters'] = is_array($filters) && count($filters) <= 30 ? $filters : [];
        }
        // Drop unsupported parameters, including arbitrary SQL controls and price ranges.
        // Replace both bags: RememberLocation also writes to the request bag on GET.
        $request->query->replace($clean);
        $request->request->replace([]);

        return $next($request);
    }

    public static function positiveInteger(mixed $value, int $max = PHP_INT_MAX): ?int
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]);

        return $id === false ? null : $id;
    }
}
