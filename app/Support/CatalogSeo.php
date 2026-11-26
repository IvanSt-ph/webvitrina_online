<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;

final class CatalogSeo
{
    /** Metadata only: consume normalized input without changing the request or listing. */
    public static function forListing(Request $request, string $baseUrl, int $page = 1): array
    {
        // RememberLocation mutates the query bag; server QUERY_STRING retains the URL input.
        $originalQuery = HeaderUtils::parseQuery((string) $request->server->get('QUERY_STRING', ''));
        $hasExplicitRegion = array_key_exists('country_id', $originalQuery)
            || array_key_exists('city_id', $originalQuery);

        $parameters = [];
        foreach (['q', 'sort', 'country_id', 'city_id', 'category_id', 'user_id', 'filters', 'per_page'] as $key) {
            if (in_array($key, ['country_id', 'city_id'], true) && ! array_key_exists($key, $originalQuery)) {
                continue;
            }

            $value = $request->query($key);
            if (filled($value)) {
                $parameters[$key] = $value;
            }
        }

        // Even default-valued explicit controls use the conservative noindex policy.
        // Explicit regional keys count even when empty/array input was removed by normalization.
        $noindex = $request->routeIs('search') || $hasExplicitRegion || $parameters !== [];
        if ($page > 1) {
            $parameters['page'] = $page;
        }

        $url = $baseUrl . ($parameters === [] ? '' : '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));

        return [
            'url' => $url,
            'canonical' => $noindex ? null : $url,
            'robots' => $noindex ? 'noindex,follow' : 'index,follow',
        ];
    }
}
