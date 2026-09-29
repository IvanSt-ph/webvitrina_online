# UI-CONSISTENCY-01

## Audit before changes

Final verification baseline: main / origin/main at `21821ed` (TYPOGRAPHY-01). All 13 pre-existing modified Blade files are retained. The audit table below records the original pre-unification presets; shared state CSS is already present in the typography commit. This completion adds only shared button focus/disabled/reduced-motion rules and finishes the Blade integration. No business logic, routes, controllers, authentication, checkout, CSP, or icon libraries are changed.

22 principal state presets identified: 20 used presets and 2 legacy component definitions with no current call sites found. The initial pass reported 20; checking shared button call sites identified two additional presets. This is a classification of visual behavior in the inspected navigation/components, not a count of every unique Tailwind string in the repository. Semantic status badges and destructive actions are not navigation selection.

Colors below use Tailwind shade notation; `brand` already aliases indigo, `neutral` aliases slate. “Browser” means no explicit component focus-visible treatment.

| Component / source under resources/views | Normal | Hover | Active / selected | Focus | Weight | Color | Background | Border | Radius |
|---|---|---|---|---|---|---|---|---|---|
| Shared seller/admin sidebar, layouts/seller + admin/layout | Neutral link | Brand tint | Brand tint + ring | Browser | 500 | neutral-600 / brand-700 | transparent / brand-50 | ring brand-100 | xl |
| Buyer sidebar, layouts/buyer-layout | Neutral link | Brand tint | Ring + 3px inset stripe + semibold | 2px outline | 500 / 600 | neutral-700 / brand-700 | transparent / brand-50 | ring + stripe | xl |
| Orders tabs, shop/orders | Muted link | Darker text | Bottom line | Browser | 600 | slate-500 / indigo-700 | transparent | 2px transparent / indigo-600 | square |
| Buyer profile navigation, buyer/profile | Muted link | Darker text | Bottom line | Browser | 500 | gray-500 / indigo-600 | transparent | 2px transparent / indigo-600 | square |
| Review tabs, buyer/reviews/index | Muted link | Darker text | Adds bottom border | Browser | 500 | gray-500 / indigo-600 | transparent | none / 2px | square |
| Seller profile buttons, profile/edit | Neutral button | Brand text | Adds bottom border | Browser | 500 | gray-600 / indigo-600 | transparent | none / 2px | square |
| Chat filter pills, chats/index | Bordered pill | Neutral tint | Tinted pill | Browser | 600 | slate-600 / indigo-700 | white / indigo-50 | slate-200 / indigo-200 | xl |
| Store filter pills, seller/show | Bordered pill | Brand tint + border | Tint + shadow | Browser | 600 | slate-600 / indigo-700 | white / indigo-50 | slate-200 / indigo-200 | full |
| Seller/admin order status filters | Bordered pill | Neutral tint + border | Brand tint | Browser | 400 | slate-600 / indigo-700 | transparent / indigo-50 | slate-200 / indigo-200 | lg / xl |
| Seller order action filters | Bordered pill | Neutral tint | Rose tint | Browser | 400 | slate-600 / rose-700 | transparent / rose-50 | slate-200 / rose-200 | lg |
| Conversation rows, chats/partials/list | White card | Border + shadow | Indigo card + shadow | Browser | 600 name, 400 preview | slate-900/500 | white / indigo-50 | white / indigo-200 | 2xl / 3xl |
| Notification rows, notifications/index | Neutral row | Neutral tint | Unread dot; moderation uses rose | Browser | 600 title | slate-900/500 | white / rose-50 at 60% | section dividers | section 2xl |
| Account menu, components/dropdown-link | Neutral link | Neutral tint | None | Background only, outline removed | inherited | gray-700 / slate-900 | transparent / slate-50 | none | 2xl |
| Public header icons, layouts/navigation | White bordered button | Translate, shadow, icon scale | Local selected styles | Browser | mixed | slate-600 / indigo-600 | white / indigo-50 | slate-200 / indigo-200 | 2xl |
| Buyer bottom navigation, layouts/mobile-bottom-nav | Muted icon + label | Scale + glow | Scale, line, some pulse | Browser | 600 labels | neutral-500 / indigo-600 | transparent + glow | small top indicator | mixed |
| Seller bottom navigation, layouts/mobile-bottom-seller-nav | Muted icon + label | Brand text | Mostly color only | Browser | 500 | neutral-500 / indigo-600 | transparent | none | mixed |
| Primary button, components/primary-button | Brand button + shadow | Translate + heavier shadow | Native pressed behavior | 2px ring, on focus | 600 | white | brand-500/90 / brand-600 | brand-400/30 | xl |
| Secondary button, components/secondary-button | White bordered button | Brand tint | Native pressed behavior | 2px ring, on focus | 600 | neutral-700 / brand-700 | white / brand-50 | neutral-200 / brand-200 | xl |
| Shared CSS primary button, app.css wv-btn-primary | Solid brand + small shadow | Darker brand | Native pressed behavior | Browser | 600 | white | brand-600 / brand-700 | none | xl |
| Shared CSS secondary button, app.css wv-btn-secondary | White border | Brand tint + border | Native pressed behavior | Browser | 500 | neutral-700 / brand-700 | white / brand-50 | neutral-200 / brand-200 | xl |
| Legacy components/nav-link, no calls found | Neutral underline link | Neutral bottom line | Brand bottom line, dark text | Border changes, outline removed | 500 | neutral-500 / neutral-900 | transparent | 2px | square |
| Legacy components/responsive-nav-link, no calls found | Neutral link | Tint + left border | Brand tint + 4px left border | Tint + border, outline removed | 500 | neutral-600 / brand-700 | transparent / brand-50 | 4px | square |

Product/store review: storefront filters use the pill preset above; product cards and store follow/contact actions use their own action/card presentation. No need to convert those actions into navigation tabs. Notification unread/error colors convey content status and should remain distinct from current-page styling.

## Selected design direction

Reuse Tailwind tokens; no new palette or CSS variables. Shared state classes only: `wv-ui-tab`, `wv-ui-pill`, `wv-ui-menu-link`, `wv-ui-focus`, and existing `wv-sidebar-link` / `wv-btn-*`.

- Neutral text: neutral-600; selected text/icon: brand-700.
- Hover: neutral-50 and brand-700; selected surface: brand-50.
- Navigation weight: 500 in both normal and selected states; no geometry change.
- Sidebar: remove ring and heavy buyer-only stripe; use a quiet 2px brand-300 inset marker as a non-color shape cue.
- Tabs: always reserve 2px bottom border; selection uses brand-600 underline, with no pill surface.
- Pills: preserve each component's radius/spacing; selection adds a thin text underline and brand-200 border, without a shadow.
- Focus-visible: 2px brand-600 outline, inset 2px so scroll containers do not clip it. Solid primary buttons use an outward 2px offset to separate the ring from their brand background. Focus is independent of selection.
- Transition: colors only, 150ms; disabled under reduced motion for these components.

## Scope

Apply the shared states to all three sidebars, orders/reviews/profile tabs, chat/store/order filter pills, public account dropdown, and notification header actions. Existing wv-btn-* consumers also inherit the shared button state styling. Preserve URLs, form methods, Alpine state, and asynchronous storefront filter replacement.

One selection-only correction was necessary: PHP converts a null array key to an empty string. Seller/admin order filters previously compared that key strictly with null, so “Все” never became active. The Blade selection condition now recognizes the empty-string key; controller filtering and link generation are unchanged. Seller action filters do not simultaneously mark “Все” as current.

Seller profile mobile section headers are now native buttons with aria-expanded / aria-controls, retaining the existing Alpine toggle behavior and adding keyboard activation.

Public header motion, bottom navigation motion, legacy unused components, full conversation cards, and broader auth-page restyling are documented follow-up work, not silently included in this scoped pass. Their current design can be addressed separately without expanding this patch into a redesign.

Shared primary/secondary/danger button components now use `wv-ui-button`: consistent focus-visible outline, native disabled state (50% opacity, no pointer interaction), color-only 150ms transitions and explicit reduced-motion support. Primary hover translation and large shadows are removed; semantic danger colors and existing 500/600 button weights remain. Admin mobile open/close controls have accessible names; the opener exposes aria-expanded.

## Before / after for visual review

| Component | Before | After |
|---|---|---|
| Buyer sidebar | 600 active weight, ring, 3px saturated stripe, 22px icons | 500 throughout, no ring, 2px light inset marker, aligned 20px icons |
| Seller sidebar | Shared tinted/ring state; no explicit keyboard focus; no current-page semantics | Same quiet state as buyer/admin, explicit focus, aria-current, scrollable short viewport |
| Orders tabs | 600 weight, separate hover treatment | 500 weight, same brand foreground and neutral hover as sidebar, retained 2px underline |
| Buyer settings | Different gray/indigo text shades, no dedicated focus | Shared underline tab state, keyboard outline, aria-current |
| Seller settings | Active border introduced extra height; mobile headers mouse-only | Reserved border prevents state height shift; native mobile buttons support keyboard activation |
| Chat/store/order pills | Different hover colors, shadows and weights; selected mostly color-only | Shared 500 weight, neutral hover, brand selection, thin underline as shape cue; radii preserved |

Screenshots are local review artifacts under `storage/app/ui-consistency/` (ignored by Git), generated from authenticated test responses with fake users. The seller sidebar stays hidden on mobile as before; its mobile screenshot shows the existing seller shell.

| View | Desktop | Mobile |
|---|---|---|
| Buyer sidebar | [1440px](../storage/app/ui-consistency/buyer-sidebar-1440.png) | [390px, expanded](../storage/app/ui-consistency/buyer-sidebar-390.png) |
| Seller sidebar | [1440px](../storage/app/ui-consistency/seller-sidebar-1440.png) | [390px shell](../storage/app/ui-consistency/seller-sidebar-390.png) |
| Orders tabs | [1440px](../storage/app/ui-consistency/orders-tabs-1440.png) | [390px](../storage/app/ui-consistency/orders-tabs-390.png) |
| Profile navigation | [1440px](../storage/app/ui-consistency/profile-navigation-1440.png) | [390px](../storage/app/ui-consistency/profile-navigation-390.png) |

## Verification

- Final Vite build succeeds; existing >500kB JavaScript chunk warning remains. No JavaScript dependency or configuration changes.
- npm audit: 0 vulnerabilities.
- All 16 changed Blade files pass PHP lint; artisan view:cache succeeds.
- SecurityHeadersTest: 3 tests, 46 assertions, passing.
- Public/typography response regression: 33 pages/scenarios, 69 assertions, passing.
- Temporary focused integration check: 17 rendered pages/scenarios, 44 assertions, passing. Buyer, seller and admin fixtures use the guarded local testing database inside rolled-back transactions. Includes current-page markers, “Все”, explicit order statuses/action filters, tabs and pills.
- Edge browser final navigation pass: 34 desktop/mobile page loads, 485 element checks, including account dropdown, seller action/status filters, admin status filter and mobile profile buttons. Separate compatibility/public pass: 32 page loads at 1440/390/720px, Cyrillic/Latin Manrope 400–800 confirmed with actual rendered glyphs, 13 local font files, no external font requests or body overflow.
- Final shared-button check after the reduced-motion fix and rebuild: primary/secondary/danger at 1440px and 390px (6 combinations), stable hover geometry, visible focus, disabled opacity/pointer suppression/native click suppression/focus exclusion, and reduced-motion transitions all pass. Uses actual Blade-rendered components mounted in the test page; no form is submitted.
- Verified computed 500 navigation weight (existing 500/600 shared-button weights retained), 2px brand focus outline, unchanged hover/selection element dimensions; the focused pass also verifies no hover position shift. Seller profile Enter activation and storefront AJAX filter/current-state replacement pass.
- Browser uses actual Laravel-rendered test HTML and response CSP headers, local built assets, and isolated browser contexts. Unrelated currency/cart background calls are intercepted; this is a UI smoke check, not a live-account end-to-end test. No real user records are changed.
- Calculated contrast: normal text on white 7.58:1; active text on brand-50 7.07:1; focus on brand-50 5.62:1; primary button text 6.29:1.
- No JavaScript exceptions, CSP violations or font failures observed. Existing external ui-avatars.com images are network-blocked in this environment; storefront product fallback `/storage/default/no-image.png` returns 404. Neither resource is introduced by this patch.
- No new external runtime URLs; icon libraries and Vite/CSP unchanged. git diff --check passes. No full test suite, commit, push or deploy.

## Files changed

This task changes the three shared button components (`primary-button`, `secondary-button`, `danger-button`), `resources/css/app.css`, `resources/views/layouts/seller.blade.php`, `resources/views/admin/layout.blade.php`, `resources/views/admin/orders/index.blade.php`, `resources/views/buyer/profile.blade.php`, `resources/views/buyer/reviews/index.blade.php`, `resources/views/chats/index.blade.php`, `resources/views/components/dropdown-link.blade.php`, `resources/views/notifications/index.blade.php`, `resources/views/profile/edit.blade.php`, `resources/views/seller/orders/index.blade.php`, `resources/views/seller/show.blade.php`, `resources/views/shop/orders.blade.php`, and this report.

`resources/views/layouts/buyer-layout.blade.php` remains modified from the preceding buyer-sidebar task; its existing grouping, mobile disclosure, badge hooks and current-page semantics are preserved in this final diff.

### Purpose of each changed file

| File | Purpose |
|---|---|
| resources/css/app.css | Shared button focus, native disabled styling and reduced-motion selector; Manrope import unchanged |
| resources/views/layouts/buyer-layout.blade.php | Preserve grouped buyer navigation, disclosure, badge hooks, current-page semantics and shared link states |
| resources/views/layouts/seller.blade.php | Shared sidebar link states, icon alignment, scrolling and current-page semantics |
| resources/views/admin/layout.blade.php | Shared sidebar states, navigation label and named mobile menu controls |
| resources/views/admin/orders/index.blade.php | Shared status pills and correct selected All state |
| resources/views/buyer/profile.blade.php | Shared profile tabs and current-page semantics |
| resources/views/buyer/reviews/index.blade.php | Shared review tabs with reserved underline |
| resources/views/chats/index.blade.php | Shared filter pills and selected state |
| resources/views/components/dropdown-link.blade.php | Shared account-menu hover and focus |
| resources/views/components/primary-button.blade.php | Stable hover geometry, quieter shadow, shared focus/disabled, reduced motion |
| resources/views/components/secondary-button.blade.php | Shared focus/disabled and color-only transitions with reduced motion |
| resources/views/components/danger-button.blade.php | Same interaction rules while preserving destructive-action colors |
| resources/views/notifications/index.blade.php | Shared action-button states and explicit row focus |
| resources/views/profile/edit.blade.php | Stable desktop tab borders and keyboard-operable mobile section buttons |
| resources/views/seller/orders/index.blade.php | Shared status/action pills and mutually correct All selection |
| resources/views/seller/show.blade.php | Shared storefront filter states compatible with AJAX replacement |
| resources/views/shop/orders.blade.php | Shared buyer order tabs and current-page semantics |
| docs/ui-consistency-01.md | Audit, implementation scope, evidence and remaining issues |

## Remaining issues / verdict

The deliberately deferred presets are listed in Scope. Existing mobile order-tab truncation and seller mobile navigation complexity remain outside this task. The admin off-canvas still uses its existing transform-based hiding without a focus trap/inert treatment; this is not a complete accessibility audit or modal-navigation redesign. Existing image failures above are unrelated to interactive states. The existing seller All counter still compares the PHP array key with null; its displayed total was not repaired because counter/business logic is outside this state-only patch. No blocking issue remains in the selected scope.

READY FOR VISUAL REVIEW
