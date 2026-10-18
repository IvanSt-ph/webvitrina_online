# MOBILE-UX-01 — final mobile UX pass

## BASELINE

2026-09-30. `main`, `HEAD = 0f31cdf` (`Улучшение обработки отсутствующих изображений`). Before edits, `git status --short` was empty; the requested baseline guard passed. No commit, push, deployment or staging setup.

## AUDIT

Widths: **390, 430, 720 px**; desktop comparison: **1440 px**. Edge/Playwright with production Vite assets. The full matrix covers 45 rendered scenarios per width; the subsequent chat/layout regression adds seller chat/list and repeats affected buyer pages:

| Area | Pages/states |
|---|---|
| Public | Home with/without banners, sorted catalog, category list, category, shop, four product/image variants |
| Buyer | Cabinet, profile, favorites, populated cart, orders, order detail, reviews, chat list, conversation |
| Seller | Cabinet, products, create/edit product, orders, order detail, profile, followers, finance, analytics, plans, support, help index, chat list/conversation |
| Admin | Dashboard, orders, products, categories, users, banners, banner edit, reviews, chats |
| Auth | Login, login with remembered account and long email, register, forgot password |

Fixtures are generated through the real Laravel routes/controllers/Blade templates using transaction-scoped users and records in the existing approved `webv3_testing` database. Long Russian product, shop and user names are included. The remembered-account variant renders the actual login Blade view with synthetic account data and the login response headers. Test writes roll back; real accounts and banners are not edited.

## CONFIRMED FINDINGS / FIXES

| Finding | Fix |
|---|---|
| Buyer order tabs truncate «Мои действия», «Завершённые», «Отменённые» at 390/430 px. | Full, nonshrinking labels in a horizontally scrollable tab strip; readable 14 px text and existing active styles. |
| Cart quantity buttons are 36×36 px, without accessible names; order-detail mobile link is an unnamed eye icon. | Mobile 44 px controls and accessible names; existing desktop sizing retained. |
| Cart checkout panel reserves only 48 px for bottom navigation; the checkout button partially overlaps navigation. | Reserve 64 px below the mobile panel content. Quantity/checkout requests and calculations remain unchanged. |
| Buyer conversation uses a full viewport height in addition to the mobile menu header, placing the composer below the first screen. | Let the buyer flex shell allocate the viewport height between header and content; chat/list fill the available parent height. Seller and desktop chat are also rechecked. No messaging/polling changes. |
| Seller popup links are 28 px high; cabinet popup lacks profile/storefront links and viewport-height constraint. | Extend the existing popup, add profile/storefront links, 44 px menu targets, bounded height and internal scrolling. No new navigation system. |
| Seller popup triggers lack expanded state; close icons lack names; Escape/focus return missing. | `aria-controls`, bound `aria-expanded`, named close buttons, Escape and return focus to the corresponding trigger, `x-cloak`, visible focus on menu links. Active cabinet state includes seller cabinet/followers/plans/profile. |
| Admin drawer trigger/close have small icon-sized hit areas. | 44×44 px controls; existing drawer/overlay behavior retained. |
| Admin banner form at 390 px clips fields and Save beyond the right edge, despite no document overflow. | Explicit zero-minimum single-column grid tracks before larger breakpoints; constrain the admin flex child with `min-w-0`. Name/enlarge mobile cropper close control. |
| Admin products' `min-w-max` tab strip pushes List/Grid controls offscreen at 720 px. | Constrain the strip; scroll its full labels internally and keep view controls accessible. |
| Wide admin users table clips rightmost actions at 1440 px with long fixture content. | Local horizontal table scrolling; existing mobile cards unchanged. |
| Remembered-account login with a long email expands its implicit grid track and clips the form on mobile. | Explicit single-column track and `min-w-0` on its flex children; mobile remembered-account controls enlarged. Auth handlers/cookies/security unchanged. |

## BUYER MOBILE

Existing buyer menu and bottom navigation retained. Order tabs can be scrolled to the last tab; labels are not ellipsized. Product/shop title truncation inside compact cards remains intentional; detail pages retain their existing content. Quantity controls and order links have accessible names. Checkout panel separation is checked by button/navigation rectangles, not by the overlapping decorative backgrounds of fixed containers. Conversation height now accounts for the mobile buyer menu header; the composer is checked at viewport heights 900, 740 and 500 px.

## SELLER MOBILE

The existing six-item bottom navigation remains. Additional destinations live in the existing cabinet popup. Both popups support close button, outside click and Escape. Tests check focus return for close/Escape, menu target height, viewport bounds, scrolling to logout and profile availability. Height checks also use a 740 px viewport, shorter than the main 900 px page smoke.

## ADMIN MOBILE

Existing off-canvas navigation and mobile card/list patterns retained. Tests cover open, close, Escape/focus return, overlay click and scrolling to the last navigation item. Banner fields fit at 390 px, and the existing cropper opens/closes with its actions in the viewport. Tables and long tab strips scroll locally where appropriate.

## AUTH MOBILE

Login, registration, reset request and remembered-account rendering inspected. A long remembered email no longer stretches the login form. Only the login template changed; authentication behavior is covered by the existing AuthenticationTest, including remembered login and forgetting an account.

## OVERFLOW / TABS / LONG LABELS

The browser checks document scroll width **and** form/control rectangles to detect clipping hidden by an outer `overflow-hidden`. Controls inside intentional scroll containers are assessed separately. Shop product carousels and wide admin tables are intentional internal scrolling. Critical order tab labels retain complete text. Compact remembered identity text still truncates intentionally within the account selector.

## TOUCH TARGETS / FORMS / ACCESSIBILITY

Changed mobile icon actions use approximately 44×44 px targets; seller menu rows are at least 44 px high. Native buttons/links are preserved. Seller popup state and names are exposed; menu link focus outlines are explicit. Buyer/admin Escape focus behavior is retained. No focus-trap framework, general accessibility rewrite or blanket resizing of all controls was introduced.

Seller product forms, profile/auth controls and admin banner inputs are included in the viewport checks. No form action, validation rule, upload processing or business logic was changed.

## DESKTOP REGRESSION

1440 px remains part of the smoke matrix. Mobile-only size changes use existing responsive breakpoints. Admin users gain internal scrolling when long content exceeds the available table width; desktop hierarchy and mobile cards are retained. Existing Manrope, colors, button hierarchy, Remix Icons and image placeholders are preserved.

## TESTS

- Transactional render smoke: `php vendor/phpunit/phpunit/phpunit storage/app/mobile-ux-01/MobileUxSmokeTest.php` — **1 test, 139 assertions passed**.
- Existing auth tests: `php vendor/phpunit/phpunit/phpunit tests/Feature/Auth/AuthenticationTest.php` — **8 tests, 43 assertions passed**.
- Existing JS tests: `node --test tests/js/image-fallback.test.mjs` — **4 passed**. No dedicated seller popup unit suite exists; inline Alpine changes are exercised in the browser.
- `php artisan view:cache` — passed.
- PHP syntax check of all eleven changed **compiled** Blade templates — passed (`storage/app/mobile-ux-01/lint.php`). No standalone application PHP files changed.
- `npm run build` — passed; existing >500 kB chunk warning remains.
- `npm audit` — **0 vulnerabilities**. Initial restricted-network attempt failed; the authorized registry retry succeeded. No dependency or lockfile changes.
- `git diff --check` — passed.
- Full `php artisan test` was **not** run.

## BROWSER SMOKE

Full matrix: **180 page loads passed** with zero document overflow, unexpected clipped form controls, JS/CSP errors, unexpected missing assets or broken images (`storage/app/mobile-ux-01/final-results.json`). After the final chat-height correction, **44 affected page loads passed** with the same checks: buyer cabinet/profile/favorites/cart/orders/order/reviews and buyer/seller chat lists/conversations (`affected-results.json`). Screenshots are named `<page>-<width>.png`.

Interactive results are in `interactions-results.json`: **24 groups passed**, including three desktop groups where mobile-only navigation is not applicable. The 21 applicable groups check buyer/seller/admin menus, order tab scrolling, cart controls/panel overlap and the banner cropper. Additional composer checks: **8 groups passed**, each asserting viewport bounds at heights 900/740/500 px (`chat-results.json`).

The harness loads current server-rendered HTML with response security headers and actual local production JS/CSS/fonts/images. It captures JavaScript exceptions, console errors/CSP errors, unexpected missing assets and visibly broken images. API polling, external services and map tiles are mocked; no browser purchase, logout, deletion or other write is submitted. This is rendered-page/layout smoke, not live-server end-to-end checkout or a physical iOS/Android test.

Expected fixture failures are distinguished from regressions: intentionally absent product/avatar/category files and the absent derivative of a valid banner recover through the existing image fallback; the synthetic missing private attachment has its existing unavailable state. External map tiles use a local stand-in, and login video content is not verified. The first audit's last desktop pages overlapped a rebuild and had stale CSS URLs; those observations were discarded and repeated against a stable final build.

## FILES CHANGED / SCOPE REVIEW

| File | Scope |
|---|---|
| `resources/views/shop/orders.blade.php` | Full tab labels and accessible mobile order link |
| `resources/views/shop/cart.blade.php` | Quantity hit areas/names and bottom-panel spacing |
| `resources/views/layouts/buyer-layout.blade.php` | Available viewport height for the chat layout |
| `resources/views/chats/index.blade.php` | Chat list fills its allocated parent height |
| `resources/views/chats/show.blade.php` | Conversation fills its allocated parent height |
| `resources/views/layouts/mobile-bottom-seller-nav.blade.php` | Existing seller popup destinations, scrolling, touch and keyboard behavior |
| `resources/views/admin/layout.blade.php` | Drawer hit areas and constrained content flex child |
| `resources/views/admin/products/index.blade.php` | Internal tab scrolling |
| `resources/views/admin/users/index.blade.php` | Internal desktop table scrolling |
| `resources/views/admin/banners/form.blade.php` | Responsive form tracks and cropper close control |
| `resources/views/auth/login.blade.php` | Responsive login/remembered-account layout and hit areas |
| `docs/mobile-ux-01.md` | Audit and verification record |

Temporary fixtures, browser scripts, logs and screenshots remain under ignored `storage/app/mobile-ux-01/`, consistent with previous passes. Controllers, models, services, migrations/schema, configuration, permissions, auth backend, checkout/order lifecycle, currency, stock, backup/restore, image fallback architecture, typography architecture and Vite configuration are unchanged.

## REMAINING NON-BLOCKING ISSUES / DELIBERATELY UNCHANGED

- Secondary seller help pages still use their existing standalone mobile layout without the cabinet bottom navigation. Core seller flows retain the existing navigation; no help-navigation refactor was added.
- Existing admin drawer does not implement a full focus trap/inert background; this was explicitly outside the focused pass.
- Compact secondary controls and legacy pagination are not globally resized. Pagination with a large data set and physical-device safe-area/virtual-keyboard behavior need visual review; the smoke's seeded lists are short.
- External map/video availability, live polling and server-side POST outcomes are outside this layout smoke. Existing backend tests are not a claim of live browser end-to-end coverage.
- Existing production build chunk-size warning remains. No unrelated performance/refactoring work was opened.

## FINAL VERDICT

**READY FOR VISUAL REVIEW**

Final scope check: 11 Blade templates and this document. `git status --short`, `git diff --check`, `git diff --stat` and `git diff --name-only` reviewed. No backend/business-logic/configuration changes. No full test suite, commit, push, deploy or staging setup performed.
