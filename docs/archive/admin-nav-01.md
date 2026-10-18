# ADMIN-NAV-01

## STRUCTURE BEFORE

The admin sidebar contained 16 existing destinations in three broad groups: Work, Catalog, and Management. Users, promotion tools, shop levels, and system tools were mixed into those groups. There was no direct route back to the public storefront.

## STRUCTURE AFTER

- Work: Dashboard, Orders, Chats, Disputes, Reviews, Reports.
- Catalog: Products, Categories.
- Users: Users, Shop levels.
- Promotion: Banners, Advertising.
- System: Backups, Release checklist, Activity log, Settings.

The 16 destinations, their order within each functional area, counters, route parameters, labels, and icons are preserved. Only their grouping changed.

## ROUTE MAPPING

| Navigation item | Named route | Active match |
|---|---|---|
| Dashboard | `admin.dashboard` | `admin.dashboard*` |
| Orders | `admin.orders.index` | `admin.orders.*` |
| Chats | `admin.chats.index` | `admin.chats.*` |
| Disputes | `admin.disputes.index` | `admin.disputes.*` |
| Reviews | `admin.reviews.index` | `admin.reviews.*` |
| Reports | `admin.product-reports.index` | `admin.product-reports.*` |
| Products | `admin.products.index` | `admin.products.*` |
| Categories | `admin.categories.index` | `admin.categories.*` |
| Users | `admin.users.index` | `admin.users.*` |
| Shop levels | `admin.seller-plan-requests.index` | `admin.seller-plan-requests.*` |
| Banners | `admin.banners.index` | `admin.banners.*` |
| Advertising | `admin.ads.index` | `admin.ads.*` |
| Backups | `admin.backups.index` | `admin.backups.*` |
| Release checklist | `admin.production-checklist` | `admin.production-checklist*` |
| Activity log | `admin.activity.index` | `admin.activity.*` |
| Settings | `admin.profile` | `admin.profile*` |

All named routes exist inside the existing `auth` and `AdminMiddleware` route group. The original sidebar had no per-item permission conditions, so none were removed or changed.

## BACK TO STOREFRONT

A separate “На витрину” link below the WebVitrina branding uses the existing `home` named route. It opens in the same tab, is visually separated from admin sections, and never receives an admin active state.

## ICONS

The existing Remix Icon system is unchanged. All original sidebar classes and the added local `ri-arrow-left-line` class are present in `resources/css/remixicon.css`. No dependency or CDN was added.

## ACTIVE STATES

Admin links continue to use the UI-CONSISTENCY-01 `wv-sidebar-link` and `wv-sidebar-link-active` styles. The route matching logic and `aria-current="page"` behavior are preserved. The storefront link uses the existing quieter `wv-ui-menu-link` hover/focus treatment.

## MOBILE

The existing off-canvas architecture and breakpoint are preserved. The opener now explicitly controls `admin-sidebar`; Escape and the close control return focus to the opener. The navigation remains independently scrollable, while branding, storefront return link, and footer remain outside the scrolling list.

## ACCESSIBILITY

The sidebar and inner navigation retain accessible labels. Mobile open/close controls have names, the opener exposes `aria-expanded` and `aria-controls`, icons are decorative, active pages expose `aria-current`, and Escape restores focus.

## TESTS

Final verification: Blade/PHP lint and Blade cache pass; a focused Laravel test covers 12 primary admin pages with 266 assertions; the production build passes; `npm audit` reports zero vulnerabilities. Edge browser smoke covers 36 page loads and 360 grouped checks at 1440, 720, and 390 pixels. It verifies ordering, route targets, active state, local icons, scrolling, footer separation, mobile open/close/Escape focus return, and a real navigation to the rendered public storefront. `git diff --check` passes.

## REMAINING ISSUES

The off-canvas menu retains the existing transform-based implementation and does not implement a focus trap or inert background. A complete modal-navigation refactor is outside ADMIN-NAV-01. The public homepage fixture still requests the pre-existing missing `/storage/banners/sale1.jpg`, producing one 404 console entry per viewport after the storefront navigation; ADMIN-NAV-01 does not change image fallback or storage.
