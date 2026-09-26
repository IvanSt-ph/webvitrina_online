# PROD-08: public catalog input validation

## Audit of the implementation before changes

| Endpoint | Query inputs affecting products | Previous validation |
| --- | --- | --- |
| `/`, `/search`, authenticated `/products` | `q`, `user_id`, `country_id`, `city_id`, `category_id`, `sort`, `per_page`, `page` | Search trims/strips tags/truncates to 100 after accepting an unchecked type; LIKE escaping; IDs cast to integers; fixed sort branches; per_page capped at 100 without a lower bound |
| `/category/{slug}` | `q`, `country_id`, `city_id`, `filters[attribute_id]`, `sort`, `page` | Bound slug lookup/404; fixed sort branches; unchecked attribute structure, membership and ranges; fixed 20 items |
| `/category-ajax/{slug}` | slug, `page` | Bound slug lookup/404; fixed 20 items; other filters were already ignored |
| `/search/suggest` | `q` | Trim, minimum 2 characters, LIKE escaping; unchecked type/length; fixed limits 5 products / 4 categories / 4 shops |
| `/recommendations` | `page` | Integer cast and minimum 1; fixed 20 items and bounded recommendation candidate sets |
| `/seller/{identifier}` | identifier, `filter`, `page` | Bound slug / numeric user lookup; filter allowlist `all`, `new`, `sale`, `hit`; fixed 20 items |
| `/p/{identifier}` | identifier; `page` for reviews | Bound product/old-slug lookup; related products fixed at 4; reviews 10 per page |
| `/category`, authenticated `/categories` | No product query parameters | Category tree only |
| `/u/{user}` | Bound user | Product count and fixed recent review list only |
| `/sitemap.xml` | No query parameters | Fixed product/category/shop queries, maximum 1000 each |

There is no registered public catalog API. The countries/cities endpoint uses model binding and only lists geography. Seller/admin management endpoints are separate from the public catalog.

`RememberLocation` already validates positive location IDs, existence and country/city consistency, including session values. It executes before the new route middleware.

Confirmed risks: arrays where strings are expected can cause PHP/view errors; zero/negative pagination sizes and unbounded page offsets; unchecked attribute IDs/shapes/values and search suggestion length. Unknown nested parameters can also break hidden-input rendering.

SQL injection, arbitrary SQL identifiers, table names, relation names and sort directions were **not** confirmed: sort branches and raw expressions were fixed, and search/attribute values were bound. There were no implemented public min/max price, brand, subcategory, specifications, status, stock or limit query controls. This task does not introduce them.

## Boundary contract

`NormalizeCatalogInput` is attached explicitly to catalog routes and layout-bearing public product/user pages. It replaces the query bag with normalized supported keys and clears the GET request bag populated by location middleware. Unsupported keys are dropped, including `column`, `order`, `direction`, `limit`, price ranges and arbitrary nested input. No redirect loops or new JSON error protocol: invalid query input is ignored/defaulted, and normal responses remain HTTP 200. Invalid/missing route identifiers remain 404.

- Search: string only, trim, maximum 100 Unicode characters. Empty catalog search leaves the catalog unfiltered; suggestions shorter than 2 characters return empty lists. Existing repository tag stripping, relevance and wildcard escaping remain; category title LIKE retains its existing `%`/`_` wildcard semantics. No new search engine.
- Sorting allowlist: `popular`, `new`, `price_asc`, `price_desc`, `rating`, `benefit`. Existing query branches remain authoritative. Catalog price variants use `price ASC/DESC`, rating uses `reviews_avg_rating DESC`, benefit uses the fixed `(price / GREATEST(stock, 1)) ASC`; new/popular/default use descending ID. Category price/rating/new branches retain their existing behavior; popular/benefit/default retain the existing descending-created-at fallback. Seller filter branches remain unchanged.
- Pagination: page 1–10000; malformed/out-of-range page becomes 1. Main catalog per_page 1–100; malformed/out-of-range becomes 20. This retains the existing maximum 100 and default 20. Other endpoints retain fixed sizes; per_page/limit cannot increase them. Valid normalized query parameters remain in existing pagination links.
- IDs: category/user IDs must be positive platform integers and exist. Geography retains its existing validation. Slug/identifier route values are at most 255 characters and contain Unicode letters/numbers, underscore or hyphen; lookups still verify existence. User profile route binding remains Laravel's existing binding.
- Prices: no implemented min/max price filter exists, so nonnumeric, negative, reversed, huge, overprecision and array price input is ignored uniformly. No conversion or decimal rule is added for a nonexistent feature. Stored currency prices, session currency and existing price sorting are unchanged.
- Attributes: `CatalogAttributeFilters` normalizes after category resolution, using filterable attributes for that category/descendants. Maximum 30 attribute entries and 50 selected values per attribute. Unknown/foreign/non-filterable IDs are ignored. Selection values must be strings up to 255 characters, in configured options or attached color IDs where applicable. Nested/associative selections are ignored. Text values stay bound.
- Numeric attributes: only `from`/`to`, signed decimal strings with at most 12 integer and 6 fractional digits; empty bounds removed, malformed/reversed range ignored. Negative characteristics remain valid (for example temperature). Zero now counts as an actual bound. No SQL column or relation comes from an attribute key.

## Scope and remaining limitations

Category AJAX already ignored search/sort/attribute filters; it continues doing so, with sanitized pagination links. Fixing its filtering functionality is a separate task. Category numeric attribute comparisons still use the existing string-backed `value` column; this patch does not redesign comparison/storage semantics. Existing category wildcard behavior and currency-independent `price` sorting remain unchanged. The page cap bounds offsets but is not a replacement for database performance work or rate limiting.

No migrations, Blade changes, checkout/stock/image/address/account-deletion changes are part of PROD-08. Pre-existing working-tree changes were preserved.

## Regression coverage and verification

`PublicCatalogValidationTest` covers malformed scalar/array inputs, SQL-like payloads, unknown sort/column/direction, negative/zero/huge/non-numeric pagination, ignored malformed/reversed price filters, Unicode search length, literal catalog/suggestion wildcards, all sort options, valid category/attribute filtering including zero bounds, foreign/malformed attributes, query-string preservation, route 404s, public endpoint responses, authenticated catalog alias and PRB/MDL/UAH display.

Tests use the existing `Tests\TestCase` guard requiring MySQL `webv3_testing` at `127.127.126.9:3306` with `APP_ENV=testing`.

Verified on 2026-09-25:

- Focused `PublicCatalogValidationTest|ProductSearchTest|CurrencyServiceTest|CheckoutCurrencyTest`: **23 passed, 177 assertions**.
- Full `php artisan test`: **420 passed, 3071 assertions**, 362.01 seconds.
- `php -l`: all six changed/added PHP files passed.
- No Blade files changed by PROD-08; feature tests rendered the affected views successfully.
- `git diff --check`: passed.

PROD-08 files: `app/Http/Middleware/NormalizeCatalogInput.php`, `app/Support/CatalogAttributeFilters.php`, `app/Http/Controllers/CategoryController.php`, `app/Repositories/ProductRepository.php`, `routes/web.php`, `tests/Feature/PublicCatalogValidationTest.php`, and this report.
