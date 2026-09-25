# Deployment Notes

Эта памятка нужна перед выкладкой WebVitrina на Linux-сервер.

## Права файлов

Веб-серверу нужны права на запись только в:

- `storage/`
- `bootstrap/cache/`

Остальной код приложения должен быть доступен для чтения, но не для записи веб-сервером.

Пример для сервера, где проект лежит в `/var/www/webvitrina`, деплой делает пользователь `deploy`, а PHP-FPM/nginx работает от группы `www-data`:

```bash
sudo chown -R deploy:www-data /var/www/webvitrina
sudo find /var/www/webvitrina -type f -exec chmod 644 {} \;
sudo find /var/www/webvitrina -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/webvitrina/storage
sudo chmod -R 775 /var/www/webvitrina/bootstrap/cache
sudo chmod 600 /var/www/webvitrina/.env
```

Если на сервере веб-пользователь называется не `www-data`, замени его на реальное имя или группу: часто это `nginx`, `apache` или `www`.

Не используй `chmod -R 777` для проекта.

## Очереди

Для продакшна рекомендуется:

```env
QUEUE_CONNECTION=database
```

Миграция таблицы `jobs` уже есть в проекте. На сервере после настройки `.env` выполни:

```bash
php artisan migrate --force
```

Очередь должна обрабатываться постоянным worker-процессом:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=60
```

Вручную в терминале worker держать нельзя. Его нужно запускать через Supervisor или systemd.

Пример Supervisor-конфига лежит здесь:

```text
deploy/supervisor-webvitrina-worker.conf.example
```

После каждого деплоя нового кода выполняй:

```bash
php artisan queue:restart
```

Так Laravel мягко перезапустит worker, и он подхватит новый код.

После настройки worker проверь, что он действительно обрабатывает задачи:

```bash
php artisan queue:health-check --timeout=15
```

Команда кладёт маленькую проверочную job в очередь и ждёт, пока worker её выполнит. Если `QUEUE_CONNECTION=sync`, команда специально падает: `sync` не проверяет настоящий worker.

Минимальная проверка очереди перед релизом:

```bash
php artisan migrate --force
php artisan queue:restart
php artisan queue:health-check --timeout=15
php artisan queue:failed
```

`queue:failed` должен быть пустым или содержать только разобранные старые ошибки.

## Scheduler и бэкапы

На сервере должен быть включён Laravel scheduler. Для Linux cron:

```cron
* * * * * cd /var/www/webvitrina && php artisan schedule:run >> /dev/null 2>&1
```

Даже если в приложении пока нет регулярных задач, scheduler нужен как стандартная часть продакшн-окружения: его проще включить сразу, чем вспоминать после добавления очередной фоновой задачи.

Бэкапы должны покрывать:

- базу данных;
- `storage/app/public`, где лежат загруженные изображения;
- `storage/app/private/chat-images`, где лежат приватные вложения чатов;
- файл `.env` отдельно в защищённом месте или систему секретов.

Встроенная команда создаёт совместимый с проверкой backup:

```bash
php artisan backup:run
```

Она уже запланирована в `routes/console.php`. Настройки в `.env`:

```env
BACKUP_DIR=/var/backups/webvitrina
BACKUP_DAILY_AT=03:15
BACKUP_MAX_AGE_HOURS=30
BACKUP_KEEP_DAYS=14
```

Достаточно минутного cron `php artisan schedule:run`, указанного выше. Отдельный ежедневный cron дублировал бы запуск. `BACKUP_COMMAND` текущим кодом не используется.

В каждой копии должны быть `database.sql.gz`, `storage-public.tar.gz`, `storage-private-chat-images.tar.gz`, `manifest.json` и `SHA256SUMS`. Старые копии без private archive считаются неполными; старый `deploy/backup-webvitrina.sh.example` с текущим форматом несовместим.

Проверка свежести, обязательных файлов и SHA256:

```bash
php artisan backup:health-check --max-age-hours=30
```

Если команда падает, backup нельзя считать рабочим. Исправь проблему и проверь ещё раз.

## Команды после деплоя

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

`APP_KEY` должен быть сгенерирован один раз при первоначальной настройке окружения и затем храниться стабильно. Не генерируй новый ключ на каждом деплое: это инвалидирует сессии и может сделать ранее зашифрованные данные нечитаемыми.

Перед запуском проверь:

```bash
composer audit
npm audit --omit=dev
php artisan test
```

После первого backup обязательно проверь восстановление: backup считается рабочим только после успешного restore на отдельной базе или тестовом окружении.

Минимальный restore-аудит:

```bash
mkdir -p /tmp/webvitrina-restore-check
tar -tzf /var/backups/webvitrina/LATEST/storage-public.tar.gz | head
tar -tzf /var/backups/webvitrina/LATEST/storage-private-chat-images.tar.gz | head
gunzip -t /var/backups/webvitrina/LATEST/database.sql.gz
mysql --host=127.0.0.1 --user=restore_user --password restore_test_db < <(gunzip -c /var/backups/webvitrina/LATEST/database.sql.gz)
php artisan backup:restore-files /var/backups/webvitrina/LATEST --force
```

`LATEST` замени на имя последней папки backup. Тестовая база должна быть отдельной от production.

## Логи

Основные файлы логов:

```text
storage/logs/laravel.log
storage/logs/twilio.log
storage/logs/registration.log
```

Если пользователь видит сообщение `Не удалось отправить SMS. Попробуйте позже.`, реальную причину смотри в `storage/logs/twilio.log`.
