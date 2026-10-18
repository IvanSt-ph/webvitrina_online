# IMAGE-FALLBACK-01

Baseline: `721082e` (`main`, `origin/main`). Initial `git status --short` was empty. No commit, push or deployment performed. Audit and verification date: 2026-09-29.

## IMAGE AUDIT

The audit covered Blade, application models/controllers/services, JS/CSS, public assets, seeders/migrations and a read-only query of the local application database. Searches included `<img`, `background-image`, `asset`, `Storage::url`, `/storage/`, avatar/image fields, thumb/medium, defaults, placeholders, `onerror` and external URLs.

| Image type | Source / helper before changes | Previous fallback; could fail? | External? | Usage |
|---|---|---|---|---|
| Product main / cards | DB `image`; Product `storageImageUrl`, `image_thumb_url` | Missing original used missing `storage/default/no-image.png`; per-card existence checks | Card gallery accepted HTTP URLs | Catalog, search, recommendations, favorites, cart, orders, buyer/seller/admin |
| Product gallery / thumb | DB `gallery`, `ImageService::thumbPath`, `storageThumbUrl`; some direct Blade storage URLs | Static thumb helper did not check files; empty product gallery could have no main URL | Card gallery bypassed model validation | Detail, card modal, seller/admin editor |
| Category image / icon | DB `image`, `icon`; Category accessors; direct admin preview URLs | Defaults existed, but missing uploaded files produced 404; subcategory error handler referenced nonexistent `no-image.webp` | No intended remote source | Menu, category cards, category admin previews |
| Shop banner | DB `banner`; Shop accessor and duplicated seller-cabinet logic | Checked local files, used branded default; accepted remote URLs | Yes | Public shop, profile, subscriptions, seller cabinet |
| Shop logo / card image | Shop `card_image_url` → owner avatar; no separate persisted logo field | Remote generated avatar if owner unavailable | ui-avatars.com | Shop cards / promoted shops |
| User avatar | DB `avatar`; User accessor plus direct storage URLs in buyer layout, followers, seller avatar editor | Remote generated avatar for null/missing; editor bypassed accessor | ui-avatars.com; possible legacy DB URLs | Buyer, seller, admin, chat, profile, remembered login accounts |
| Review photos | DB `ReviewImage.path`, accessors; direct admin modal/full-image URLs | Unchecked thumb/original URLs | No intended remote source | Product reviews, buyer reviews, admin review modal/lightbox |
| Private chat attachment | `Message.image_path`; authorized `chats.messages.image` / admin endpoint | Endpoint returns 404; browser previously displayed broken image | No | Buyer/seller/admin chat messages |
| Public banners | Banner variant fields; homepage local closure / Alpine slides | Hardcoded `storage/banners/sale1.jpg`; unconditional preload even with no banners | No | Homepage desktop/tablet/mobile |
| Admin banners | Same persisted files, separate upload/crop pipeline | Existing variants previewed correctly; missing files had no display recovery | No | Banner list and edit previews |
| Static assets | `public/images`, flags/icons, seller help illustrations | Several hardcoded help article illustrations absent; existing defaults were bright branded artwork | Map tiles are intentionally remote | Branding, help articles, geographic maps |

CSS background search found a decorative gradient, not a hidden sale1 image source. Map tiles and map behavior are outside this change. `resources/js/profile/avatar-cropper.js` contains an old `/default-avatar.png` literal, but is not imported or referenced by the active application; the active seller editor defines its cropper in Blade and now uses `avatar_url`.

## ROOT CAUSES

1. The product default depended on an absent file in writable storage.
2. Model accessors, static helpers and direct Blade URLs applied inconsistent existence/derivative rules.
3. Null/missing avatars depended on an external generated-avatar service. Several templates bypassed the accessor entirely.
4. Homepage banner fallback/preload used a nonexistent demo filename.
5. Some category error handlers redirected to another nonexistent filename; a `picture` source could override the replacement img URL.
6. Help article templates named illustrations absent from the repository.
7. Existing defaults were unsuitable for the requested neutral appearance: `no-image.png` is a purple “COMING SOON” illustration; category/shop defaults are strongly branded; the small avatar default is also purple.

## SALE1.JPG ROOT CAUSE

Exact source: `resources/views/shop/index.blade.php`. The preload helper returned `storage/banners/sale1.jpg` when the banner collection was empty; each missing responsive variant also used that filename. This explains the previous browser fixture failure independently of real uploaded banners.

Read-only local DB inspection found **zero** banner fields containing `sale1.jpg`. Both active real banners (IDs 1 and 2) have desktop, tablet and mobile WebP files, and all six exist. No DB repair or deletion is necessary. No artificial sale1 file was created.

The homepage skips preload when there is no banner. Each Banner resolves the requested variant, then another existing variant of the same banner, then the legacy `image` field, then a local placeholder. The homepage query now selects legacy `image` too. A versioned cache key avoids old serialized banner projections; existing banner create/update/delete invalidation clears both keys. Upload processing, crop behavior and deletion logic are unchanged.

## PRODUCT FALLBACK

Existing `storageImageUrl`, `storageThumbUrl`, `image_url`, `image_thumb_url`, `DEFAULT_IMAGE_PATH` and default-path recognition are retained. They now delegate to `PublicImage` for a bounded list of trusted local URLs. Legacy `storage/` and `/storage/` prefixes are normalized once; unsafe paths are rejected before URL construction. Old default path values remain supported without changing DB rows.

The real ImageService pipeline writes `medium/<uuid>.webp` and `thumb/<uuid>.webp`; it does **not** retain a separate original for new uploads. Legacy original paths are supported. No speculative original filename is invented and no derivative is generated during reads. Product detail gallery always includes the main image URL, including for empty image values. Admin quick search now returns the resolved thumb URL.

## CATEGORY FALLBACK

Image → existing icon → local generic placeholder. Standalone icon filenames keep the legacy `categories/icons/` convention. Menu/cards/admin previews use the same accessors. The nonexistent WebP fallback and conflicting redundant `picture` source were removed.

## SHOP FALLBACK

Existing local banner → generic neutral image placeholder. Remote banner values resolve locally; persisted values are unchanged. Shop card image uses the owner's local avatar policy. Seller cabinet and profile no longer build separate banner URLs. No new logo field or schema change.

## AVATAR POLICY

Existing local thumb → existing local original/medium → `images/avatar-placeholder.svg`. Null, missing, malformed and external values use the neutral local silhouette. No remote fetch/proxy is introduced. Read-only inspection found no HTTP avatar values in the current local DB and one null avatar. Current Google OAuth does not save a provider avatar; the confirmed external source was the generated fallback in User/Shop and Blade.

## REVIEW/CHAT BEHAVIOR

Review accessors use the existing Product display helpers; admin JSON includes resolved `url`/`thumb_url`, and modal/full-image links use them. Missing review files do not produce known-bad public URLs.

Private chat URLs remain authorized endpoints, never public-storage URLs. On a failed image load the browser displays “Изображение недоступно” inside the existing endpoint link. The endpoint's 404 remains visible in the network panel. Authorization, private storage and status semantics are unchanged; this is an explicit unavailable state, not a successful placeholder attachment response. Tests confirm the existing 404 for both a missing participant attachment and a stranger.

## FALLBACK HIERARCHY

| Context | Order |
|---|---|
| Product/review thumb | Existing thumb → supplied medium/legacy original → generic placeholder |
| Product/review full image | Existing supplied medium/legacy original → generic placeholder |
| Avatar | Existing thumb → supplied local file → avatar placeholder |
| Category | Existing image → existing icon → generic placeholder |
| Shop banner | Existing local file → generic placeholder |
| Public banner | Requested variant → desktop/tablet/mobile alternatives → legacy image → generic placeholder |
| File disappears after HTML | One browser replacement with trusted local fallback |
| Private attachment load failure | Explicit unavailable text; endpoint link/status preserved |

Only two small new assets are used: `public/images/image-placeholder.svg` and `public/images/avatar-placeholder.svg`. They are static SVG markup without scripts, embedded assets or remote references. Existing artwork is preserved. Missing help illustrations use the generic placeholder.

## STORAGE LINK STATUS

**PASS**: `public/storage` is a Windows Junction targeting `C:\webvitrina_online\storage\app\public`. The six real banner variants are reachable through it. `storage/app/public/default/no-image.png` is absent: that was an individual missing default, not a broken junction.

PublicImage builds storage URLs without reading the disk or `public/storage`. The browser attempts the bounded candidate chain. The existing independent `ProductionHealth` storage-link check remains unchanged and must remain part of deployment verification. Browser recovery does not repair or certify infrastructure; network errors remain observable. No link or production infrastructure was changed.

## SECURITY

Reject traversal segments, absolute paths, backslashes, URL schemes, protocol-relative URLs, percent encodings, control characters and URL query/fragment syntax before public disk access. URL path segments are encoded. Fallback arguments are application-owned literals. Private disks are not queried by PublicImage. No raw HTML, arbitrary file-serving endpoint, arbitrary remote proxy or CSP expansion was added. `SecurityHeaders.php`, auth and permissions are unchanged.

## PERFORMANCE

PublicImage is a pure URL builder. Resolving 100 catalog products performs zero filesystem calls; each image has at most a small fixed candidate chain. Only the single-banner editor checks up to four exact public-disk paths to establish a real crop source. No directory listing, recursive scan, synchronous conversion, remote image request or per-card database lookup occurs in display helpers. A lightweight capture-phase browser listener handles annotated images, including dynamic Alpine images, and checks eager failures that occurred before module startup. No image framework or mutation observer is introduced.

## ACCESSIBILITY / UX

Existing card dimensions, aspect containers and object-fit classes remain. Product detail images now have the product title as alt; buyer profile avatar has an explicit alt. Other contextual/decorative alt values are preserved. Browser replacement does not modify alt, dimensions or layout classes. Chat failure text remains inside its original actionable link. Static branding/icons and cropper source images are not globally intercepted.

## TESTS

- Focused ProductImageUrlTest, PublicImageFallbackTest and ImageServiceTest: **19 tests, 191 assertions passed**. Coverage includes null/empty/default/unsafe paths, valid/missing original and thumb, legacy prefixes, local/external avatars, category/icon fallback, shop/review, missing banner variants, and per-request directory reuse.
- Additional transactional render smoke in `storage/app/image-fallback-01/ImageFallbackSmokeTest.php`: **1 test, 94 assertions passed**, using the explicitly designated local `webv3_testing` database. Database writes roll back. No real user/banners were changed. Null product image is tested at model level; the real DB column is NOT NULL, so the browser fixture uses empty string for that case.
- PHP lint for changed PHP files and compiled Blade: PASS. `php artisan view:cache`: PASS. `git diff --check`: PASS.
- `npm run build`: PASS, with the existing large-bundle warning. `npm audit`: **0 vulnerabilities** after retry with network access; the sandboxed attempt could not reach the registry.
- No full `php artisan test` suite, commit, push or deploy was run.

## BROWSER SMOKE

Edge/Playwright: **62 page loads** at 1440 and 390 px, plus **3 targeted tablet page loads at 1024 px**. All pass with zero unexpected public-image 404s, visible broken/empty images, JS exceptions or CSP errors. The tablet check cycles all three fixture slides. Fixtures include two banners using the six actual existing uploaded files, plus a banner with missing variants to exercise fallback. Admin previews continue to load.

Public: home, empty-banner home, catalog, categories, category, shop and four product scenarios. Buyer: cabinet, profile, favorites, cart, orders/detail, reviews, chats/detail. Seller: cabinet, products, orders/detail and profile. Admin: products, categories, users, banners/edit, reviews and chats. Scenario data includes valid, missing, default and empty product images; null/missing/external/local avatars; missing review/category/shop images; private missing attachment.

Browser race checks verify actual failed requests recover through the shared handler, `picture/srcset` cannot override fallback, a 160×120 image retains size and alt, subsequent source changes work, and a failed fallback makes only one request rather than looping. These intentionally failed requests and private attachment 404s are expected test stimuli, not unexpected application failures.

Evidence: `storage/app/image-fallback-01/browser-results.json`, `browser-tablet-results.json`, renderer/browser scripts and screenshots. The browser loads fresh Laravel-rendered HTML with response security headers and real built/local image assets through Playwright routing. Background API responses are stubbed; this is rendered-page browser smoke, not a live-server login/upload/checkout E2E test. Working upload pipeline is covered by existing ImageService unit tests and inspection; no real upload is overwritten.

## REMAINING ISSUES

- A file deleted after response generation can still cause its first image request to fail. The client recovers without repeated requests; zero initial 404s cannot be guaranteed in that race.
- A missing private attachment intentionally keeps endpoint 404 semantics. Restoring lost private files is outside this task.
- Deployment must ship the two new SVG assets and built JS. Missing deployment assets/link remain operational errors, checked independently rather than treated as successful storage repair.
- Full live-server workflow E2E and a production-size media-directory benchmark were not performed. No blocking issue was found in the requested image scenarios.

## FILES CHANGED

The application changes are display URL resolution, local placeholders, reusable browser recovery, template opt-ins and focused regression tests. The complete path list follows.

- `app/Http/Controllers/Admin/BannerController.php`
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Http/Controllers/ProductController.php`
- `app/Models/Banner.php`
- `app/Models/Category.php`
- `app/Models/Product.php`
- `app/Models/ReviewImage.php`
- `app/Models/Shop.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `app/Support/PublicImage.php`
- `docs/image-fallback-01.md`
- `public/images/avatar-placeholder.svg`
- `public/images/image-placeholder.svg`
- `resources/js/app.js`
- `resources/js/image-fallback.js`
- `resources/views/admin/ads/form.blade.php`
- `resources/views/admin/banners/form.blade.php`
- `resources/views/admin/banners/index.blade.php`
- `resources/views/admin/categories/form.blade.php`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/categories/mobile-list.blade.php`
- `resources/views/admin/categories/table.blade.php`
- `resources/views/admin/chats/index.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `resources/views/admin/product-reports/index.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/admin/profile.blade.php`
- `resources/views/admin/reviews/index.blade.php`
- `resources/views/admin/users/edit.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/show.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/buyer/profile/general.blade.php`
- `resources/views/buyer/reviews/index.blade.php`
- `resources/views/categories/index.blade.php`
- `resources/views/categories/show.blade.php`
- `resources/views/categories/subcategories.blade.php`
- `resources/views/chats/index.blade.php`
- `resources/views/chats/partials/list.blade.php`
- `resources/views/chats/partials/messages.blade.php`
- `resources/views/chats/partials/product-context.blade.php`
- `resources/views/chats/partials/widget.blade.php`
- `resources/views/chats/show.blade.php`
- `resources/views/components/category-item.blade.php`
- `resources/views/components/product-card.blade.php`
- `resources/views/components/product/gallery.blade.php`
- `resources/views/components/product/related.blade.php`
- `resources/views/components/product/tabs.blade.php`
- `resources/views/layouts/buyer-layout.blade.php`
- `resources/views/layouts/mobile-bottom-nav.blade.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/views/layouts/seller.blade.php`
- `resources/views/profile/buyer-cabinet.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/subscriptions.blade.php`
- `resources/views/seller/cabinet.blade.php`
- `resources/views/seller/followers/index.blade.php`
- `resources/views/seller/help/articles/boost-sales.blade.php`
- `resources/views/seller/help/articles/product-optimization.blade.php`
- `resources/views/seller/help/articles/reviews-and-rating.blade.php`
- `resources/views/seller/help/articles/updates-2025.blade.php`
- `resources/views/seller/help/articles/updates-2026.blade.php`
- `resources/views/seller/help/index.blade.php`
- `resources/views/seller/help/show.blade.php`
- `resources/views/seller/orders/show.blade.php`
- `resources/views/seller/partials/avatar.blade.php`
- `resources/views/seller/products/form.blade.php`
- `resources/views/seller/products/index.blade.php`
- `resources/views/seller/show.blade.php`
- `resources/views/shop/cart.blade.php`
- `resources/views/shop/favorites.blade.php`
- `resources/views/shop/index.blade.php`
- `resources/views/shop/order-confirm.blade.php`
- `resources/views/shop/order-show.blade.php`
- `resources/views/shop/orders.blade.php`
- `resources/views/users/partials/profile-content.blade.php`
- `tests/Unit/ProductImageUrlTest.php`
- `tests/Unit/PublicImageFallbackTest.php`

## FINAL REVIEW FIXES

1. **Directory listing:** `PublicImage` is now a pure, bounded URL candidate builder. Product/review thumbnails try thumb, supplied medium/original, then a local placeholder; category and banner candidates follow their configured variant order. Catalog rendering never calls `Storage::files()` or `exists()`. The banner editor alone uses up to four exact `exists()` checks for physical crop sources.
2. **Path normalization:** A supplied `/storage/storage/photo.jpg` loses only the public URL prefix and remains `storage/photo.jpg` on disk. Legacy root `photo.jpg` generates the trusted `thumb/photo.webp` candidate. A caller-supplied `./thumb/photo.webp`, traversal, encoded traversal, absolute/local paths and external URLs remain invalid. URL path segments are encoded.
3. **Responsive images:** On an image load error, the browser removes the image `srcset` and enclosing `picture` source `srcset` before trying the next local candidate. It handles a broken source even when `img.src` already equals the placeholder, and keeps per-image tried URLs to prevent a loop. The tracked `tests/js/image-fallback.test.mjs` covers those cases.
4. **Banner cropper:** Display preview may show a placeholder, while `existingUrl` and crop buttons receive only physically present banner sources. A recrop request with no source now returns a validation error instead of silently succeeding. The two real banner records and six stored variant files were not modified; upload and deletion behavior is unchanged.

Focused verification after these fixes: ProductImageUrlTest and PublicImageFallbackTest **7 tests / 146 assertions passed**; tracked Node tests **4 passed**; render fixture smoke **1 test / 94 assertions passed**; PHP lint, Blade cache, `npm run build`, `npm audit` (**0 vulnerabilities**) and `git diff --check` passed. Targeted Edge/Playwright smoke covered seven rendered pages (home, empty home, two products, banner list/edit, chat) plus a real `picture` failure with `img.src` already equal to the fallback. No visible broken image or page error remained. The initial failed image requests are expected with client-side candidate resolution; the earlier zero-404 claim above applies only to the pre-fix implementation. No full suite, commit, push or deploy was run.

## FINAL VERDICT

**READY FOR VISUAL REVIEW**. No commit, push or deploy.
