# PROD-15 — Local acceptance

**PROD-15 CLOSED** — 2026-09-27. Baseline: `main`, `d361517d6188e74cfd347f127ab513f5eb82ac05`; исходное working tree было clean.

Локальная production-hardening работа завершена.
Следующий этап — STAGING-00.

## Подтверждённые проблемы и исправления

- Deployment/runbook допускали разобранные старые failed jobs; checklist допускал другой async queue driver. Теперь acceptance требует `failed_jobs = 0`, расследования и подтверждённого исправления до осознанного удаления записей, `QUEUE_CONNECTION=database` и shared persistent cache. Health-policy не менялась.
- Checklist пропускал `storage-private-chat-images.tar.gz`. Теперь указан полный фактический набор: `database.sql.gz`, `storage-public.tar.gz`, `storage-private-chat-images.tar.gz`, `manifest.json`, `SHA256SUMS`.
- Runbook не описывал полный writer-stop перед history/address-snapshot migrations. Добавлены остановка HTTP, workers, scheduler, backup/restore и внешних writers; ожидание in-flight mutations; проверенный backup до migrations. Описаны границы `BackupWriteBarrier` и стабильный общий lock inode.
- Добавлен recovery flow: writers остаются остановленными, при возможности forward fix; полный rollback — согласованное восстановление CODE + DATABASE + обоих FILE STORAGE roots из одной точки. Код/секреты сохраняются отдельно от backup. Слепой `migrate:rollback` запрещён; автоматического rollback нет.
- Runbook указывал только `laravel.log`; теперь указаны dated Laravel/Twilio/registration logs и `LOG_DAILY_DAYS` (default 14 дней), отдельно server logrotate для старых/emergency logs.
- LOCAL/CI tests/build/audit отделены от STAGING/PRODUCTION checks. PHPUnit после Composer `--no-dev` не требуется; test DB guard остаётся локальным.
- Старый shell backup example создавал неполную копию без текущего barrier. Помечен как неподдерживаемый и завершается до выполнения; оператор направляется к существующему `backup:run`. Backup/restore implementation не менялась.

## External assets: до и после

Все перечисленные CDN-подключения были без SRI. Итог для каждого — **self-host**, SRI не используется.

| Ресурс до | Решение |
| --- | --- |
| `https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js` | `chart.js@4.4.1`, Vite `chart.js/auto`, существующий `window.Chart` API |
| `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js` и `leaflet.css` | `leaflet@1.9.4`, JS/CSS через Vite, явные URL marker assets |
| `https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js` и `cropper.min.css` | `cropperjs@1.6.1`, Vite, существующий `window.Cropper` API |
| `https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js` и `cropper.min.css` | Тот же единственный локальный Cropper |
| `https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css` | Exact npm alias `remixicon-legacy@npm:remixicon@3.5.0`; entry `remixicon-v3.css` |
| `https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css` | `remixicon@4.1.0`; сохранены исходные версии/каскад, повторные подключения одной версии ограничены именованным `@once` |
| `https://unpkg.com/lucide-static/font/lucide.css` (без версии) | Закреплён `lucide-static@1.48.0`, CSS/fonts через Vite; два класса иконок приведены к фактическим `icon-heart` / `icon-shopping-cart` |
| Bunny CSS `https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap` (app/guest) | `@fontsource/manrope@5.3.0`, те же пять весов через Vite |
| Bunny CSS `https://fonts.bunny.net/css?family=instrument-sans:400,500,600` (welcome) | `@fontsource/instrument-sans@5.3.0`, те же три веса через Vite |
| Alpine | Внешнего CDN уже не было; существующий npm/Vite, один `Alpine.start()` |
| intl-tel-input | Уже npm/Vite; utils и флаги тоже локальные, без изменений версий |

Изменений существовавших lockfile package entries нет; добавлены только используемые runtime dependencies и транзитивный `@kurkle/color`. `npm update` не запускался.

Графики admin dashboard/seller cabinet и lazy product map теперь ждут `DOMContentLoaded`, то есть выполнения Vite modules. Остальные handlers уже выполнялись после загрузки или по действиям пользователя. Runtime libraries устанавливают globals до `Alpine.start()`. Удалена ссылка на отсутствующий `public/js/profile/avatar-cropper.js`; используемый `avatarCropper()` уже определён в подключаемом Blade partial, второй экземпляр не добавлен.

**После: внешних runtime JS/CSS подключений — 0**, включая font stylesheets. Поиск по resources/public JS не нашёл внешних script/link tags; собранный CSS не содержит внешних URL.

Отдельные категории, не CDN runtime JS/CSS:

- Изображения: `ui-avatars.com`, `*.tile.openstreetmap.org` и пользовательские external image URLs остаются по существующим правилам.
- API: `nominatim.openstreetmap.org` для геокодирования; currency API вызывается через существующий same-origin backend endpoint. OAuth/SMS/mail не менялись.
- Embedded content: `www.youtube.com` iframe, не script dependency родительской страницы.
- Source-map URL, ссылки на документацию/лицензии и SVG namespaces не считаются runtime dependency. Для source maps никаких CSP разрешений не добавлено.

## CSP

Из существующих соответствующих списков script/style/font/image sources удалены `cdn.jsdelivr.net`, `cdnjs.cloudflare.com`, `unpkg.com`; после переноса шрифтов также `fonts.bunny.net` из style/font sources. `connect-src` не менялся. Существующие `unsafe-inline`/`unsafe-eval` не расширялись; local Vite allowances, OSM/Nominatim/avatars/YouTube и production HSTS сохранены. Обновлены ожидаемые CSP в двух существующих feature tests.

## Выполненная verification

| Проверка | Результат |
| --- | --- |
| Финальный `npm ci` | PASS: 165 packages added, 166 audited |
| `npm run build` | PASS, Vite 8.0.16; 87 modules; marker assets, CSS/fonts и manifest созданы |
| `npm audit` | PASS: **0 vulnerabilities**, включая devDependencies |
| `git diff --check` | PASS |
| `php vendor/bin/phpunit tests/Unit/SecurityHeadersTest.php` | **3 passed, 45 assertions**: production/local/testing, без application boot, БД или migrations |
| 22 изменённых Blade templates | Blade directive compilation + PHP lint PASS; component tags не рендерились, приложение/БД не запускались |
| Headless Edge, production assets, DPR 1 и 2 | PASS: line/bar/doughnut Chart.js 4.4.1, Leaflet 1.9.4, реальный JS lazy product map из Blade, Cropper canvas, Alpine, phone utils, локальные иконочные/текстовые fonts |
| Browser resource/console checks | Нет JS exceptions, CSP errors или 404; Leaflet icon/shadow реально загружены, Retina использует 2x asset |

Leaflet build mapping:

- `marker-icon.png` → `assets/marker-icon-hN30_KVU.png` (natural width 25).
- `marker-icon-2x.png` → `assets/marker-icon-2x-_ZA0WGCc.png` (natural width 50 на DPR 2).
- `marker-shadow.png` → `assets/marker-shadow-f7SaPCxT.png` (natural width 41).

Browser smoke выполнялся на временном локальном static HTTP fixture с реальным `public/build`, без Laravel/БД. Внутренний currency endpoint и OSM tiles были заглушены. Это проверяет bundle/runtime/asset resolution, но не полноценные авторизованные страницы и внешние integrations. Локальный JSON результата: `storage/app/prod15-verification/browser-result.json` (ignored verification artifact).

Полный PHP suite и feature tests с БД **не запускались**, migrations не выполнялись. Два существующих feature test expectations обновлены для последующего ручного suite.

Ограничение сборки: app JS 541.73 kB minified / 177.35 kB gzip; Vite предупреждает о chunk >500 kB. Библиотеки подключены статически для сохранения существующего синхронного globals/Alpine API. Оптимизация загрузки не включена в этот этап. Пакеты иконочных шрифтов также сохраняют fallback font formats; современные браузеры используют WOFF2.

Первый `npm ci` был заблокирован Windows DLL работающего Vite; после остановки только этого Vite финальный `npm ci` успешен. Исходный Vite восстановлен с `--port 5173 --strictPort`. Ошибка сборки с entry name `remixicon-legacy.css` устранена переименованием entry в `remixicon-v3.css`: текущий Vite резервирует `-legacy`; package upgrade не понадобился.

## Manual browser / STAGING acceptance

- Главная и категория: меню, Alpine, фильтры, шрифты/иконки, mobile viewport.
- Карточка товара: lazy map после прокрутки, marker/shadow/popup; обычный и Retina экран.
- Admin: dashboard/category charts; product create/edit maps и draggable markers.
- Seller: кабинет/аналитика, product form/map, avatar/banner cropper.
- Buyer: кабинет, заказ/подтверждение, профиль, phone input и utils.
- DevTools: нет runtime JS errors/CSP violations/404; нет дублирующихся экземпляров библиотек; JS/CSS/fonts идут с собственного `/build`.

**SERVER/STAGING ONLY:** реальный HTTPS/HSTS и HTTP smoke; server env/секреты/права и `public/storage`; отсутствие `public/hot`; migrations/status в контролируемом deployment; config/route/view caches; worker и scheduler с общим cache, `failed_jobs = 0`; независимый health monitoring/alerts; свежий полный backup, manifest/SHA256 и isolated DB+files restore drill; работоспособность внешних maps/geocoding, OAuth, SMS, mail и notifications. Эти условия локальным fixture не подтверждаются.

Deploy, настройка staging/server, изменения production/dev БД, commit и push не выполнялись. PROD-16 и новые аудиты не начинались.

## Все изменённые/добавленные файлы

Список ниже включает этот отчёт; `public/build`, node_modules и локальные verification artifacts игнорируются Git и в него не входят.

- `PRODUCTION_CHECKLIST.md`
- `app/Http/Middleware/SecurityHeaders.php`
- `deploy/backup-webvitrina.sh.example`
- `docs/deployment.md`
- `docs/prod-15-local-acceptance.md`
- `docs/production-monitoring.md`
- `docs/release-runbook.md`
- `package-lock.json`
- `package.json`
- `resources/css/instrument-sans.css`
- `resources/css/lucide.css`
- `resources/css/manrope.css`
- `resources/css/remixicon-v3.css`
- `resources/css/remixicon.css`
- `resources/js/app.js`
- `resources/js/runtime-libraries.js`
- `resources/views/admin/categories/index.blade.php`
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/layout.blade.php`
- `resources/views/admin/products/create.blade.php`
- `resources/views/admin/products/edit.blade.php`
- `resources/views/components/app-layout.blade.php`
- `resources/views/components/nav.blade.php`
- `resources/views/components/product/map.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/buyer-layout.blade.php`
- `resources/views/layouts/error.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/layouts/mobile-bottom-seller-nav.blade.php`
- `resources/views/layouts/seller.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/seller/analytics/index.blade.php`
- `resources/views/seller/cabinet.blade.php`
- `resources/views/seller/partials/main.blade.php`
- `resources/views/seller/products/form.blade.php`
- `resources/views/shop/order-confirm.blade.php`
- `resources/views/shop/order-show.blade.php`
- `resources/views/welcome.blade.php`
- `tests/Feature/Auth/RegistrationTest.php`
- `tests/Feature/SecurityRegressionTest.php`
- `tests/Unit/SecurityHeadersTest.php`
- `vite.config.js`
