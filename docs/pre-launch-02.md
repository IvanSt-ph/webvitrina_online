# PRE-LAUNCH-02 — checkout token и тест баннера

Дата: 2026-09-30. Основание: `docs/pre-launch-01.md`. Проверенный HEAD: `6fd2202cc862a2434c3fcdbff684221b9e858af5` (`main`). До изменений `git status --short` показывал только незакоммиченный `docs/pre-launch-01.md`; файл сохранён без изменений.

## DATA-01: причина и изменение

`quick()` и `prepare()` сохраняли `checkout_cart`, но токен создавался только при GET `checkout.confirm`. В `CheckoutController::create()` отсутствие токена сессии выключало сразу две проверки: сравнение с токеном формы и атомарный `Cache::add`. Поэтому прямой POST на `checkout.create` после подготовки корзины мог создать заказ без одноразовой защиты.

Теперь `create()` перед созданием заказа требует непустой строковый токен в сессии и такой же строковый токен в запросе. Отсутствующий, пустой, массивный или несовпадающий токен отклоняется с переходом на страницу подтверждения. Для каждого запроса, прошедшего эту проверку и проверки актуальности корзины, безусловно выполняется атомарный `Cache::add` по хешу токена. Если ключ уже занят, заказ не создаётся. Существующие расчёты цены и валюты, блокировка товара, уменьшение остатка, транзакция и снимки заказа не менялись.

Из двух перекрывающихся POST с **одним токеном** в транзакцию может пройти только запрос, которому удалось зарезервировать ключ. При общем атомарном cache второй получает отказ до записи заказа и остатка. Репозиторий задаёт `CACHE_STORE=database`; `DatabaseStore::add()` использует `insertOrIgnore`, а колонка `cache.key` имеет первичный ключ. Ключ действует 10 минут, как до этого изменения. После ошибки пользователь может открыть `checkout.confirm`: страница проверяет и обновляет корзину и выдаёт новый токен для повторного подтверждения. Если ошибка возникла после резервирования, старый токен остаётся использованным; повторять его нельзя.

Добавлены проверки отсутствующего/пустого токена сессии, отсутствующего/пустого/неверного/массивного токена формы, успешного заказа после повторного подтверждения и перекрывающихся отправок у границы резервирования. Существующий тест повторной отправки сохранён. Прежний тест уменьшения остатка переведён на обычный путь через GET подтверждения и токен формы. Перекрытие в новом тесте управляемое и выполняется в одном PHP-процессе; реальная одновременная работа разных HTTP workers ещё не проверена.

## QA-01: тест баннера

Сценарий `test_admin_banner_edit_page_previews_legacy_image_and_exposes_cropper_controls` проверяет редактор **существующего legacy-изображения**. Раньше он создавал только путь в записи баннера, поэтому `Banner::cropSources()` правильно не находил файл и показывал placeholder. Теперь тест создаёт настоящее JPG-изображение в `Storage::fake('public')`, сохраняет полученный путь в баннере и проверяет `src` изображения предпросмотра и видимость кнопки Cropper. Production-проверка существования файла и архитектура IMAGE-FALLBACK-01 не менялись.

## Изменённые файлы

| Файл | Изменение |
|---|---|
| `app/Http/Controllers/CheckoutController.php` | Обязательная сверка токенов и безусловное одноразовое резервирование. |
| `tests/Feature/SecurityRegressionTest.php` | Тесты токенов и перекрытия, обновление успешного checkout, физический legacy-файл в fake disk для теста баннера. |
| `docs/pre-launch-02.md` | Этот отчёт. |

## Риски и пределы вывода

- Атомарность `Cache::add` между workers требует общего cache; тестовая конфигурация использует process-local `array`, поэтому новый сценарий проверяет контролируемое перекрытие внутри процесса. Конфигурацию общего cache и настоящий параллельный запрос нужно проверить отдельно на staging.
- Резервирование имеет TTL 10 минут и не является постоянным ключом идемпотентности заказа. Восстановленный устаревший снимок сессии после истечения TTL — остаточный риск. GET подтверждения также выдаёт новый токен, поэтому разные токены одной корзины не получают взаимной блокировки. Эти случаи требуют отдельного решения, если нужен более строгий контракт «одна корзина — один заказ».
- Резервирование происходит до транзакции. При её ошибке запись заказа и остатка откатывается, но токен остаётся занятым; безопасный путь повтора — снова открыть страницу подтверждения и проверить данные. Это поведение требует проверки пользовательским запуском тестов.
- Поскольку тесты не запускались по прямому запрету, компиляция PHP, работа mock cache и фактическое поведение HTTP не подтверждены исполнением. Готовность к staging по этому изменению пока не подтверждена.

## Команды для самостоятельной проверки

Запускать только на охраняемой тестовой БД `webv3_testing` по правилам `tests/TestCase.php`. Команды ниже намеренно раздельные.

### 1. Исправление checkout

```powershell
php artisan test tests/Feature/SecurityRegressionTest.php --filter=test_checkout_rejects_missing_empty_and_mismatched_tokens
php artisan test tests/Feature/SecurityRegressionTest.php --filter=test_overlapping_checkout_submissions_with_one_token_create_one_order
```

### 2. Тест баннера

```powershell
php artisan test tests/Feature/SecurityRegressionTest.php --filter=test_admin_banner_edit_page_previews_legacy_image_and_exposes_cropper_controls
```

### 3. Непосредственно затронутые regression tests

```powershell
php artisan test tests/Feature/SecurityRegressionTest.php --filter=test_checkout_decrements_stock_when_order_is_created
php artisan test tests/Feature/SecurityRegressionTest.php --filter=test_checkout_token_cannot_create_the_same_order_twice
php artisan test tests/Feature/SecurityRegressionTest.php --filter=test_checkout_requires_new_confirmation_when_product_price_changes
php artisan test tests/Feature/CheckoutNotificationTest.php --filter=test_mid_checkout_failure_rolls_back_all_sellers_without_notifications
php artisan test tests/Feature/OrderStockTest.php
php artisan test tests/Feature/CheckoutCurrencyTest.php
php artisan test tests/Feature/OrderAddressSnapshotTest.php
```

## Выполненные и невыполненные проверки

Выполнены чтение HEAD/status, статический просмотр изменённых веток и прямых тестов, `git diff --check` (без ошибок). Не выполнялись тесты любого вида, PHP lint, build, dependency audit, миграции, изменения действующих данных, commit, push или deploy. Итог: **изменения подготовлены, runtime-проверка ожидает самостоятельного запуска команд выше**.
