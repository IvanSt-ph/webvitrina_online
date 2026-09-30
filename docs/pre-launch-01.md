# PRE-LAUNCH-01 — security, performance and production readiness audit

Дата: 2026-09-30. Объект: `C:\webvitrina_online`.

## Baseline и границы проверки

- `main`, `HEAD = 6fd2202cc862a2434c3fcdbff684221b9e858af5` (`Улучшение мобильного интерфейса`); `git status --short` до аудита был пустым. Последние коммиты: `6fd2202`, `0f31cdf`, `721082e`, `269427b`, `21821ed`.
- Прочитаны прежние результаты `docs/prod-15-local-acceptance.md`, `docs/prod-10-verification.md`, `docs/backup-restore-safety.md`, `docs/image-fallback-01.md`, `docs/mobile-ux-01.md` и runbook. Они использованы как карта ранее закрытых вопросов, а не как доказательство текущего состояния.
- Проверены исходники критических HTTP/CLI границ, production build, dependency audits, маршруты, scheduler и выбранные существующие тесты. Независимые статические проходы не подтвердили уязвимость в авторизации, OAuth, сессиях, чатах и загрузках. Плагин Codex Security завершил Standard scan с **0 findings и честно отмеченным частичным файловым покрытием**: исследованы 8 важных поверхностей, не выполнена построчная проверка всех 666 файлов. Его scan ID: `cfa03b14-3f0e-491f-97dc-5345761bca88`. Завершающий анализ checkout **после** этого scan обнаружил DATA-01; число findings плагина не заменяет итоговый вывод этого документа.
- Функциональные тесты использовали только охраняемую тестовую БД `webv3_testing` на `127.127.126.9`; guard в `tests/TestCase.php:29-47` отвергает иную БД/окружение. Никаких запросов восстановления к действующим БД/storage, нагрузочных тестов, миграций основной БД, обновлений зависимостей, commit, push или deploy не было.

## Executive summary

**Вердикт: FIX BEFORE STAGING.** Подтверждённых P0/P1 уязвимостей и подмены цены в проверенном коде нет. Завершающий статический проход обнаружил **DATA-01**: сервер принимает оформление заказа без `checkout_token` и пропускает защиту от повторной отправки; параллельное создание дубликатов остаётся обоснованным, но не исполненным сценарием. Отдельно выбранный фокусный regression gate красный: **303 passed, 1 failed, 2628 assertions, 593.56 s**. Единственный сбой тестов — устаревшее ожидание URL несуществующего файла баннера. До staging нужно исправить DATA-01 после отдельного согласования финансовой логики, согласовать тест баннера с нынешним контрактом и получить зелёный фокусный прогон.

Локальные проверки не дают статуса production-ready. TLS/proxy, секреты, worker/scheduler, внешние интеграции, фактические права и восстановление серверного backup проверяются на staging.

## Подтверждённые проблемы

| ID / приоритет | Место и механизм | Подтверждение и последствия | Минимальное действие |
|---|---|---|---|
| DATA-01 / **P2**, до staging | `CheckoutController.php:79-85,130-141` сохраняет `checkout_cart` без токена; токен выдаётся только в `confirm()` (`:239-241`). В `create()` обе проверки условны: при пустом session `checkout_token` пропускаются `hash_equals` (`:352-356`) и атомарный `Cache::add` (`:394-396`), после чего `:416-461` создаёт заказ и уменьшает stock. `routes/web.php:369-370` не включает session blocking; схема `database/migrations/2024_01_01_130000_create_orders_tables.php:11-16` имеет уникальный номер заказа, но не ключ checkout. | **Подтверждение обхода:** ранее пройденный `SecurityRegressionTest.php:2987-3006` ожидает успешное создание заказа с `checkout_cart`, но без `checkout_token`. Таким образом, защиту можно обойти через `POST /checkout/confirm` (prepare), затем `POST /checkout/create` до GET страницы подтверждения. **Возможное последствие:** два параллельных POST, прочитавших одну сессию при достаточном stock, могут создать два заказа и дважды уменьшить остаток. Этот конкурентный сценарий статически воспроизводим по ветвлению, но в данном аудите не запускался. | Требовать непустой server-side token и совпадающий token запроса до создания заказа; для каждого принятого checkout всегда выполнять атомарное одноразовое резервирование. Добавить focused test на tokenless запрос и конкурентный replay; изменение финансового кода выполнять отдельным согласованным шагом. |
| QA-01 / **P2**, до staging | `tests/Feature/SecurityRegressionTest.php:4321-4336` создаёт запись баннера с `image = banners/legacy/old.webp`, не создавая сам файл, и требует увидеть `storage/banners/legacy/old.webp`. `app/Models/Banner.php:33-46` допускает crop source только после `Storage::disk('public')->exists($path)`; `resources/views/admin/banners/form.blade.php:10-13` подставляет placeholder для отсутствующего источника. | Фокусный прогон падает на assertion в строке 4334. Это ложное ожидание прежнего поведения; реальная ссылка на отсутствующий файл не выдаётся. Последствие — красный regression gate и невозможность честно считать выбранные тесты пройденными. Другие сценарии баннеров (загрузка, обновление, recrop существующего legacy файла) прошли в том же запуске. | Изолированно создать реальный файл в тестовом public disk, если сценарий должен проверять существующий legacy source; либо изменить ожидание на placeholder/скрытый crop, если сценарий проверяет отсутствующий файл. Затем повторить **только этот тест** и соответствующий focused gate. |

## Security findings

DATA-01 — подтверждённый обход защиты от повторной отправки checkout, который может быть использован покупателем для повторных заказов при параллельных запросах. Других подтверждённых проблем в проверенных auth/IDOR/injection/upload границах нет. Это результат статического анализа и существующих тестов, а не гарантия отсутствия всех уязвимостей.

- **Auth/OAuth/сессии.** Google callback связывает по provider identity и не присоединяет существующий локальный аккаунт только по email (`app/Http/Controllers/Auth/GoogleController.php:24-53`); профильные тесты Google прошли. Смена/сброс пароля инвалидируют другие сессии и remembered access (`app/Http/Middleware/EnsurePasswordSessionIsCurrent.php:20-39`; 12 тестов `PasswordSessionRevocationTest` прошли). Регистрация, reset и вход имеют throttling в `routes/auth.php:14-62`.
- **Buyer/seller/admin и IDOR.** Seller/admin группы защищены в `routes/web.php:419,505-507`; товар продавца проверяется политикой `app/Policies/ProductPolicy.php:18-33`; заказы проверяют `user_id`/`seller_id` (`app/Http/Controllers/OrderStatusController.php:20-24,49-51,87-91,136-138`). ChatController проверяет участника и принадлежность сообщения беседе (`app/Http/Controllers/ChatController.php:431-438,584-588`). В regression tests прошли проверки чужих чатов, чужих галерей, неадминистративного доступа и экранирования сообщений.
- **CSRF/XSS/SQL/mass assignment.** Изменяющие HTTP маршруты находятся под Laravel `web` middleware (`bootstrap/app.php:13-20`); проверенные Blade sinks экранируют сообщения, запросы применяют ORM/bindings, а сортировки каталога/admin выбираются из allowlist (`app/Repositories/ProductRepository.php:239-249`, `app/Http/Controllers/Admin/CategoryController.php:48-55`). Подтверждённого обхода не найдено. CSP в `app/Http/Middleware/SecurityHeaders.php:13-27,82-113` содержит `unsafe-inline` и `unsafe-eval` для действующего Alpine; это ослабление дополнительной защиты, **не самостоятельное доказательство XSS**.
- **Файлы и интеграции.** `app/Rules/ImageUploadConstraints.php:22-98` ограничивает тип/размеры, `app/Services/ChatImageService.php:14-21` перекодирует приватный upload; private image отдаётся через контроллер после проверки участника. Currency proxy использует фиксированный HTTPS источник и cache (`app/Http/Controllers/CurrencyProxyController.php:41-72`). В маршрутах найдены Google OAuth callback и Twilio Verify; принимающего payment/webhook endpoint нет, поэтому webhook signature здесь неприменима. В `php artisan route:list --json` обнаружено 223 маршрута, из них 84 `admin/*` и 28 с явным `throttle`; отсутствие throttle на каждом маршруте само по себе не подтверждает уязвимость.

## Data integrity findings

Подтверждённый дефект **DATA-01** затрагивает обязательность одноразового checkout token. Подмена цены, повреждение stock при проверенных конкурентных отменах и изменение исторического адреса не подтверждены.

| Инвариант | Текущие доказательства |
|---|---|
| Клиент не задаёт итоговую цену | `CheckoutController.php:352-396` сверяет checkout token, валюту и пересчитанные сервером строки; `:416-468` в транзакции заново проверяет заблокированный `Product` и записывает snapshot цены. `CheckoutCurrencyTest` и checkout/security tests прошли. |
| Параллельные операции и отмена | `CheckoutController.php:416,438,461`; `Order.php:253-290` блокирует persisted order/product и восстанавливает остаток один раз. `OrderStockTest` и `OrderStockConcurrencyTest` прошли. |
| Дубликаты | `CheckoutController.php:394` применяет одноразовый cache token **только при наличии** session token. `:509` удаляет session checkout после успешного запроса, но не сериализует уже начавшиеся запросы. См. DATA-01. |
| История и адрес | `Order.php:16-41` создаёт snapshot из принадлежащего покупателю сохранённого адреса и запрещает его изменение; тесты `OrderAddressSnapshotTest` и `AccountDeletionTest` прошли. |
| Чужие заказы | `OrderController.php:20-24`, `Seller/OrderController.php:25-29,89-91` ограничивают buyer/seller выборки и detail. Regression tests прошли. |

Онлайн списания карт в текущем приложении нет: `Order.php:94-102` описывает оплату картой при получении. Финансовый код не изменён.

## Performance measurements и анализ

Методика: локальный `npm run build` на HEAD, анализ сгенерированных размеров Vite и статический проход по запросам/загрузке изображений. Измерений TTFB, Core Web Vitals или `EXPLAIN ANALYZE` на репрезентативном объёме данных не было; прогнозы времени ответа не приводятся.

| Область | Измерение/наблюдение | Вывод и ожидаемый эффект возможного изменения |
|---|---|---|
| JS/CSS | Vite 8.0.16: **88 modules**, build **4.36 s**; `app` JS **543.23 kB minified / 177.95 kB gzip**, `utils` **264.23 / 59.93 kB gzip**; app CSS **178.84 / 26.20 kB gzip**. Vite предупреждает о chunk >500 kB. | `resources/js/app.js:1` статически включает Chart.js, Leaflet, CropperJS из `resources/js/runtime-libraries.js:1-8` на страницах с общим app entry (`resources/views/layouts/app.blade.php:45`, `layouts/guest.blade.php:16`). **P3, необязательное**: после staging network/CPU profile выделить page-specific imports. Целевой измеримый эффект — уменьшить gzip app entry на размер реально вынесенного chunk и JS parse/eval для страниц без этих функций; конкретные миллисекунды до профилирования неизвестны. |
| Шрифты/иконки | Self-hosted Manrope/Instrument Sans; build включает WOFF2, legacy WOFF/TTF/EOT/SVG assets. Крупнейший SVG Lucide **20,713.13 kB** как сгенерированный файл. | Сам факт наличия файла в build не означает его загрузку браузером. На staging измерить Resource Timing; без сетевого доказательства удаление форматов не предлагается. |
| Каталог/поиск | `ProductRepository.php:69-154` использует eager loading и пагинацию с ограниченным `per_page`; `:170-212` делает `Product::count()` и содержит `%LIKE%` поиск; индексы title/fulltext определены migrations `2025_10_10_153738` и `2025_10_10_154147`. | На staging с реальным объёмом проверить p95 search и `EXPLAIN` наиболее частых запросов. Добавление индексов без плана и анализа существующих индексов не обосновано. |
| Кабинеты/чаты | Seller/admin order lists: `Seller/OrderController.php:28-58`, `Admin/OrderController.php:30-124` используют eager loading и paginate. Чаты: `ChatController.php:21,32-48,149-157` ограничены 20 диалогами и последними 50 сообщениями. | На проверенных путях не найдено подтверждённого N+1 или неограниченной загрузки сообщений. Дополнительный query profiling нужен только при росте данных/жалобах. |
| PublicImage | `app/Support/PublicImage.php:1-58` строит фиксированный список URL без обращения к storage; `Banner.php:33-46` выполняет максимум четыре `exists()` только в editor одного баннера. | `PublicImageFallbackTest` прошёл, включая большой каталог без storage calls. Массового filesystem I/O на карточках не обнаружено. |
| Scheduler/queue/images | `php artisan schedule:list` показывает heartbeat ежеминутно, queue health каждые 5 минут, backup 03:15, purge 03:30; дубликата в активном schedule нет. Upload перекодирование синхронно и ограничено `ImageUploadConstraints`. | Серверные p95 и worker backlog/timeout нужно измерить на staging. Локального доказательства блокирующей проблемы нет. |

## Dependencies и production configuration

- Локальные версии: PHP **8.4.8**, Laravel **12.69.2**, Composer **2.9.2**, Node **22.18.0**, npm **10.9.3**; `composer.json` требует PHP `^8.4.1` и Laravel `^12.0`.
- `composer audit --format=json`: exit 0, `advisories: []`, `abandoned: []`. `npm audit --json`: exit 0, **0 vulnerabilities** (prod/dev included). Это моментальный снимок известных advisory, а не аудит собственного кода. Lockfiles не менялись.
- `npm run build`: exit 0; `php artisan route:list --json`: exit 0; `php artisan schedule:list`: exit 0. Сгенерированный `public/build` игнорируется Git; tracked working tree не изменён.
- В `.env.example:5-8,51-55,75` и `PRODUCTION_CHECKLIST.md:6-14` зафиксированы `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, database cache/queue, SMTP. `config/session.php:172-190` задаёт Secure/HttpOnly/SameSite через конфиг. `SecurityHeaders.php:74-113` задаёт CSP/HSTS в production. `config/filesystems.php` разделяет public и private storage. Это **требования/дефолты репозитория**, фактические параметры staging не просмотрены; секреты не выводились.
- Trusted proxy/TLS termination, CORS на reverse proxy, document root, `public/storage` symlink, private/backup permissions, стабильный `APP_KEY`, mail delivery, логирование и shared cache между HTTP/worker/scheduler требуют проверки на сервере. В коде не найдено подтверждённого обхода; отсутствие серверной информации не приравнивается к уязвимости.

## Backup/recovery

- `RunBackup.php:20-81,125-126` получает exclusive `BackupWriteBarrier`, создаёт MySQL consistent read snapshot и архивы public/private, manifest и SHA256; публикует backup только после полного формирования. `BackupWriteBarrier.php:15-39` использует стабильный lock inode без удаления; `RunBackup.php:333` реализует retention.
- `BackupHealth.php:10-14,119-173` проверяет полный набор и checksums; `RestoreBackupFiles.php:20-45` требует изолированный `--drill` либо явный `--force`, а `BackupStorageService.php:90-118` валидирует архивы до активации.
- Прошли тесты `PrivateBackupTest`, `BackupWriteBarrierTest`, `BackupSnapshotTest`, `BackupAtomicityTest`, `SafeRestoreTest`. Они подтверждают локальные инварианты, включая отказ при повреждении и сохранение старых данных при сбое. **Свежий реальный backup и полный DB+files restore drill на отдельном серверном окружении ещё не выполнены**; это staging gate по `docs/release-runbook.md`.

## Потенциальные риски и необязательные улучшения

| Тип | Что проверять | Приоритет/момент |
|---|---|
| Требует серверной проверки | TLS/HSTS за proxy, Secure cookie, доверенные proxy, CSP/CORS, изоляция private chat files и backups от web root. | Staging acceptance; пока не подтверждённая уязвимость. |
| Требует серверной проверки | Действующие Google OAuth callback, Twilio, SMTP, currency endpoint, worker/scheduler с общим cache и отсутствие failed jobs. | Staging acceptance. |
| Необязательное | JS split по фактически используемым Chart/Leaflet/Cropper после network/CPU profile; измерять gzip entry и p75 render/interaction до/после. | P3, не блокирует staging. |
| Необязательное | Ужесточить CSP после перехода Alpine на совместимую сборку; текущие `unsafe-inline`/`unsafe-eval` — defense-in-depth ограничение. | P3, без подтверждённого XSS. |
| Необязательное | Search `EXPLAIN`/p95 и query count на репрезентативном staging каталоге; не создавать индексы до измерения. | P3, условно. |

## Release gate

| Проверка | Статус | Доказательства | Действие |
|---|---|---|---|
| Security | **DATA-01** как business-logic abuse; иных findings нет, покрытие частичное | 2 независимых статических прохода, последующий анализ checkout; Codex Security scan ранее завершился с 0 findings | Устранить DATA-01 до staging; runtime access/TLS/integration smoke на staging. |
| Data integrity | **BLOCKED by DATA-01**; остальные проверенные инварианты PASS | Tokenless checkout допускается кодом и прошедшим тестом; stock/currency/address/account tests прошли | Исправить обязательность token и проверить tokenless/parallel replay до staging; затем контролируемый заказ/отмена на staging fixture. |
| Performance | PASS с P3 наблюдением | Vite размеры, ограниченные выборки и PublicImage unit test | Профиль p95/search/network на staging; JS split не обязателен до staging. |
| Dependencies | PASS на дату аудита | Composer/npm audit 0, build PASS | Повторить audit в release CI при развёртывании. |
| Backup/recovery | Локально PASS, серверный drill pending | 5 focused suites, code paths | Свежий полный backup + отдельная DB/files restore drill на staging. |
| Production configuration | Pending staging | Только repo defaults/runbook, фактический сервер не инспектировался | Проверить значения без раскрытия секретов, caches, TLS/proxy, storage, queue/mail/logging. |
| Staging readiness | **BLOCKED by DATA-01 и QA-01** | Обход одноразовой проверки; 303 passed / 1 failed | Согласовать отдельное исправление checkout, согласовать тест с ImageFallback контрактом и получить зелёный focused gate до deploy. |

## Staging verification checklist

1. До staging: отдельным согласованным шагом устранить DATA-01, проверить tokenless и параллельный replay; устранить QA-01 в тестовом сценарии и повторить затронутые тесты плюс focused gate. Для финальной CI-приёмки отдельно запустить полный suite командой `php artisan test` **на выделенной тестовой БД**; в этом аудите полный suite не запускался.
2. Проверить HTTPS redirect/HSTS, canonical `APP_URL`, cookie Secure/HttpOnly/SameSite, trusted proxies и отсутствие debug output; `public/` как document root, private chat images и backups недоступны напрямую.
3. Пройти buyer/seller/admin smoke: чужой order/product/chat ID даёт отказ; Google callback, reset и отзыв сессий работают с staging credentials.
4. Проверить заказ с двумя продавцами, сохранённую валюту/адрес, параллельную покупку ограниченного stock и однократное восстановление при отмене на безопасных fixture records.
5. Проверить worker и scheduler, shared persistent cache, `failed_jobs = 0`, SMTP/Twilio/OAuth/currency/geocoding и оповещения.
6. Создать и проверить полный backup (`database.sql.gz`, оба storage tar.gz, manifest, SHA256SUMS), выполнить DB+files restore drill в полностью отдельном окружении; не восстанавливать поверх работающих данных.
7. Снять p95 latency/query count для home/search/product/seller/admin/chats, `EXPLAIN` тяжёлого поиска, браузерные network/CPU метрики и 404 изображений. Сравнивать только с согласованными staging порогами.

## Evidence и test results

- Запуск: `php artisan test` с явным списком **17** файлов: `SecurityRegressionTest`, `GoogleAuthenticationTest`, `PasswordSessionRevocationTest`, `CheckoutCurrencyTest`, `OrderStockTest`, `OrderStockConcurrencyTest`, `OrderAddressSnapshotTest`, `AccountDeletionTest`, `ProductImageLifecycleTest`, `PrivateBackupTest`, `BackupWriteBarrierTest`, `BackupSnapshotTest`, `BackupAtomicityTest`, `SafeRestoreTest`, `PublicCatalogValidationTest`, `PublicImageFallbackTest`, `SecurityHeadersTest`. Итог **303 passed, 1 failed, 2628 assertions, 593.56 s**; сбой только QA-01. Среди прошедших есть tokenless checkout test (`SecurityRegressionTest.php:2987-3006`), который подтверждает DATA-01. Полный suite не запускался.
- `composer audit --format=json`: PASS, 0 advisories/abandoned. `npm audit --json`: PASS, 0 vulnerabilities. `npm run build`: PASS, warning о chunk >500 kB. `php artisan route:list --json`: PASS, 223 маршрута. `php artisan schedule:list`: PASS, 4 события.
- Изоляция тестов: `tests/TestCase.php:29-47`; никакой тест не запускался против основной БД. Время backup tests в основном обусловлено проверками ожидания lock/timeout; это не benchmark HTTP backup.
- Интерпретация audit команд: [Composer audit](https://getcomposer.org/doc/03-cli.md#audit) проверяет известные advisory/abandoned пакеты, [npm audit](https://docs.npmjs.com/cli/v10/commands/npm-audit/) обращается к registry с деревом зависимостей. Для production Laravel рекомендует `APP_DEBUG=false`: [Laravel deployment](https://laravel.com/docs/12.x/deployment).

## Final verdict

**FIX BEFORE STAGING** — подтверждён обход одноразовой проверки checkout (DATA-01), а фокусный regression gate остаётся красным из-за устаревшего теста баннера (QA-01). DATA-01 требует отдельного согласования изменения финансовой логики; код и тесты в рамках этого аудита не исправлялись. После устранения обеих проблем и зелёного фокусного прогона оставшиеся эксплуатационные проверки выполняются на staging. Фактический production readiness может быть определён только после этих серверных проверок.
