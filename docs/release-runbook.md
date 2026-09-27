# WebVitrina Release Runbook

Порядок контролируемого deployment на staging/production. Это инструкция для оператора, не автоматический deploy/rollback. Сначала пройти LOCAL/CI acceptance из [deployment.md](deployment.md) с dev dependencies. На production после `composer install --no-dev` PHP tests не запускаются; локальный test DB guard туда не переносится.

## 1. Проверить `.env`

Обязательные значения:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
CACHE_STORE=database
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@your-domain.example
BACKUP_DIR=/var/backups/webvitrina
BACKUP_MAX_AGE_HOURS=30
```

`APP_KEY` должен быть заполнен один раз и не должен меняться между деплоями.

`QUEUE_CONNECTION=database` обязателен: production queue probe отвергает другие async drivers. HTTP/CLI/worker/scheduler используют общий persistent cache.

Настройте независимый запуск `production:health-check --json`, внешний `/up` monitoring и доставку тревог по [инструкции PROD-09](production-monitoring.md). Успешный ручной запуск не подтверждает регулярный monitoring.

## 2. Установить зависимости и собрать frontend

Подготовьте новый release отдельно от работающего кода; не заменяйте код активных writers до раздела 3. Допустимо доставить `public/build` из CI вместо серверной npm-сборки. Для сборки Vite нужны npm devDependencies.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 3. Применить миграции и production cache

`php artisan down` сам по себе **не гарантирует остановку всех writers**. Особенно важны миграции `2026_09_23_000002_preserve_orders_when_accounts_are_deleted` и `2026_09_25_000001_add_address_snapshot_to_orders`: они сохраняют историю и имеют намеренно запрещённый `down()`.

До переключения кода и любой опасной production migration:

1. Закройте HTTP writers на всех узлах; включите maintenance, исключите обход через maintenance secret и прямые HTTP endpoints.
2. Остановите queue workers через их process manager с ожиданием текущих jobs и запретом автоматического перезапуска. `queue:restart` не является остановкой writers.
3. Остановите запуск scheduler (cron/timer), дождитесь уже запущенных задач, фоновых команд и in-flight HTTP/DB/file mutations. Заблокируйте другие CLI, SQL и внешние writers.
4. Убедитесь, что backup/restore не выполняется и не может стартовать параллельно, включая ручные/admin запуски. Согласуйте паузу health probes, которые записывают queue/cache и проверочные файлы.
5. При остановленных writers выполните `php artisan backup:run` и `php artisan backup:health-check --max-age-hours=30`. Зафиксируйте точный каталог, code revision и совместимые настройки/секреты; проверьте manifest и SHA256SUMS именно этой копии. В копии обязательны `database.sql.gz`, `storage-public.tar.gz`, `storage-private-chat-images.tar.gz`, `manifest.json`, `SHA256SUMS`. Успешный isolated restore drill должен быть подтверждён до acceptance.
6. Только после этого переключайте подготовленный код и выполняйте migrations/cache команды ниже. Writers остаются остановленными до проверки результата.

`BackupWriteBarrier` — кооперативный process-owned `flock`, не запрет всех записей. Он координирует поддерживаемые HTTP/jobs/file mutations и backup/restore, но не охватывает raw SQL, migrations и внешние скрипты. Все участвующие процессы должны разделять один стабильный inode `storage/framework/backup-writes.lock`; его нельзя удалять/заменять или создавать отдельно для каждого release. Блокировка backup освобождается после команды, поэтому writer-stop сохраняется силами оператора. Подробности: [PROD-10](backup-restore-safety.md).

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Если `storage:link` пишет, что ссылка уже есть, это нормально. Важно, чтобы `/public/storage` реально вёл в `storage/app/public`.

Проверьте `php artisan migrate:status`, `php artisan route:list --except-vendor` и успешность cache-команд до возобновления writers. При ошибке держите writers остановленными и используйте раздел rollback ниже.

### Rollback / recovery

Не выполняйте слепой `php artisan migrate:rollback`: указанные history migrations намеренно необратимы, MySQL DDL может оставить частично применённое состояние. Автоматического rollback в проекте нет.

При безопасно исправимой ошибке предпочтителен проверенный forward fix с остановленными writers. Если требуется полный rollback состояния, восстановите **CODE + DATABASE + FILE STORAGE** из одной проверенной точки: соответствующий code revision/build, SQL и оба storage archive одной backup-копии, с совместимыми настройками/APP_KEY. Backup сам по себе код и секреты не содержит — их нужно сохранить отдельно и связать с выбранной копией.

Перед восстановлением проверьте manifest/checksums. SQL импорт выполняется отдельно; `backup:restore-files /absolute/backup --force` восстанавливает только файлы и не блокирует весь DB+files restore. Writers остаются остановленными на всё время восстановления. Следуйте [recovery procedure PROD-10](backup-restore-safety.md), не удаляйте recovery artifacts при ошибке. После восстановления проверьте соответствие DB и обоих storage roots выбранной копии, manifest/checksums, обновите кэши. Затем контролируемо возобновите worker/scheduler/HTTP и проверьте health и smoke; maintenance закономерно мешает получить общий healthy до `up`.

## 4. Включить очередь

Worker нельзя держать открытым терминалом. Используйте Supervisor/systemd/панель хостинга.

После успешного раздела 3 возобновите process manager workers и scheduler, затем снимите maintenance (`php artisan up`) и HTTP-блокировку. Дождитесь нового scheduler heartbeat; восстановите независимые health probes. `queue:restart` ниже только обновляет уже запущенные workers.

Supervisor-шаблон:

```text
deploy/supervisor-webvitrina-worker.conf.example
```

Проверка:

```bash
php artisan queue:restart
php artisan queue:health-check --timeout=15
php artisan queue:failed
```

`queue:health-check` должен пройти; перед acceptance обязательно **failed_jobs = 0**. Старые записи тоже делают health critical. Сохраните сведения, расследуйте причину, подтвердите исправление и только затем осознанно удалите разобранные записи (`queue:forget <id>`). Не очищайте автоматически ради зелёного health. Перед retry оцените риск повторных побочных эффектов.

## 5. Настроить backup

Backup должен включать БД, `storage/app/public` и `storage/app/private/chat-images`.

Встроенная команда создаёт архив БД, отдельные архивы public storage и private chat images, `manifest.json` и `SHA256SUMS`:

```bash
php artisan backup:run
```

Она уже запланирована в `routes/console.php` на `BACKUP_DAILY_AT` (по умолчанию 03:15). Внешний cron должен вызывать `php artisan schedule:run` каждую минуту. Отдельный ежедневный cron для backup при этом не нужен.

Проверка:

```bash
php artisan backup:health-check --max-age-hours=30
```

После первого backup обязательно проверить восстановление на отдельной тестовой базе.

Isolated restore drill: используй отдельного DB user без прав на production DB.
Для файлов обязательно укажи новый пустой каталог:

```bash
DRILL_DIR=$(mktemp -d /tmp/webvitrina-restore-check.XXXXXX)
php artisan backup:restore-files /path/to/completed-backup --drill="$DRILL_DIR"
```

Production destructive restore — отдельная операция: `backup:restore-files /path/to/backup --force`
заменяет live storage и требует согласованного DB restore при остановленных writers.
Модель блокировки записей и recovery: [PROD-10](backup-restore-safety.md).

## 6. Проверить письма и уведомления

Минимально проверить:

- регистрация или сброс пароля;
- уведомление продавцу о новом заказе;
- уведомление покупателю о смене статуса заказа.

## 7. Прогнать тестовый заказ

Ручной путь:

- главная;
- категория;
- товар;
- корзина;
- checkout;
- создание заказа;
- чат по заказу;
- запрос отмены или спор;
- подтверждение доставки;
- отзыв.

## 8. Проверить мобильный checkout

Минимум на телефоне или в mobile viewport:

- товар;
- корзина;
- checkout;
- заказ;
- чат;
- кабинеты покупателя и продавца.

## 9. Финальная проверка после деплоя

```bash
php artisan migrate:status
php artisan route:list --except-vendor
php artisan queue:health-check --timeout=15
php artisan queue:failed
php artisan backup:health-check --max-age-hours=30
php artisan production:health-check --json
```

Открыть в браузере:

- `/`
- `/categories`
- `/cart`
- `/checkout/confirm`
- `/orders`
- `/my-chats`
- `/admin/production-checklist`
- `/sitemap.xml`
- `/robots.txt`

Browser smoke выполняется на production build (`public/hot` не доставляется на сервер):

- Главная и категория: меню Alpine, фильтры, иконки и шрифты.
- Карточка товара: карта после прокрутки, popup и видимый marker/shadow; проверить обычный экран и Retina/mobile.
- Admin dashboard и категории: графики Chart.js без повторной инициализации; admin product create/edit: карта и draggable marker.
- Seller: кабинет/аналитика, форма товара, карта, avatar/banner cropper.
- Buyer: кабинет, заказ, подтверждение заказа, профиль и телефонное поле.
- DevTools: нет runtime JS errors, CSP violations, 404 на JS/CSS/fonts/images и повторной загрузки Alpine/Chart/Leaflet/Cropper. JS/CSS и шрифты загружаются локально из `/build`; OSM tiles/Nominatim, avatars и YouTube остаются внешними сервисами.

Полный путь через авторизованные страницы и реальные integrations проверяется на staging; локальный smoke отдельных собранных assets его не заменяет.

## 10. После релиза

Первые сутки смотреть:

- `storage/logs/laravel-YYYY-MM-DD.log`, `twilio-YYYY-MM-DD.log`, `registration-YYYY-MM-DD.log` (retention `LOG_DAILY_DAYS`, default 14 дней); старые/emergency логи требуют server logrotate;
- failed jobs;
- свежесть backup;
- реальные письма;
- жалобы пользователей на checkout и чат.
