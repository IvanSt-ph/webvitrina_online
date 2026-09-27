# Production Checklist

Перед запуском на продакшне проверь:

- Подробная памятка по деплою: `docs/deployment.md`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `APP_KEY` сгенерирован командой `php artisan key:generate`
- `APP_KEY` не генерируется автоматически во время обычного деплоя и хранится стабильно между релизами
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `LOG_LEVEL=warning` или `error`
- `QUEUE_CONNECTION=database` обязателен: текущий production queue probe поддерживает только database driver
- `DB_USERNAME` не `root`, пароль сложный
- `ADMIN_EMAIL` задан реальным email администратора, если включено создание админа через сидер
- `ADMIN_PASSWORD` задан уникальный и сложный, если включено создание админа через сидер
- `SEED_ADMIN_USER=true` используется только осознанно; после создания админа лучше вернуть `false`
- `SEED_DEMO_PRODUCTS=false` на продакшне
- SMTP, Google OAuth и Twilio ключи не тестовые и не лежат в публичном репозитории
- Все секреты из локального `.env`, которые когда-либо могли попасть в чужие руки, ротированы
- Права на сервере настроены без `chmod -R 777`: запись только в `storage/` и `bootstrap/cache/`
- Supervisor/systemd worker для очереди настроен по примеру `deploy/supervisor-webvitrina-worker.conf.example`
- `php artisan queue:health-check --timeout=15` успешно подтверждает, что worker обрабатывает job
- Перед acceptance `failed_jobs = 0`: причины всех ошибок расследованы и исправлены; записи удаляются осознанно после подтверждения исправления, не автоматически ради зелёного health
- Cron/systemd timer для Laravel scheduler настроен: `php artisan schedule:run` каждую минуту
- Ежедневный backup БД, `storage/app/public` и `storage/app/private/chat-images` настроен через `php artisan backup:run` в Laravel scheduler
- `BACKUP_DIR` и `BACKUP_MAX_AGE_HOURS` заданы так, чтобы админский релиз-чеклист видел свежий backup
- Включён cron `php artisan schedule:run` каждую минуту; время backup задано в `BACKUP_DAILY_AT`. Переменная `BACKUP_COMMAND` кодом не используется
- В backup есть `database.sql.gz`, `storage-public.tar.gz`, `storage-private-chat-images.tar.gz`, `manifest.json` и `SHA256SUMS`
- `php artisan backup:health-check --max-age-hours=30` проходит без ошибок
- Restore backup проверен на тестовой базе, не только создание архива
- Миграции выполнены только после writer-stop и проверенного backup по `docs/release-runbook.md`; проверены `php artisan migrate:status` и `php artisan storage:link`
- После деплоя выполнены `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`, `php artisan queue:restart`
- Веб-сервер отдаёт сайт только по HTTPS, а приложение возвращает HSTS-заголовок в production
- `php artisan production:health-check --json` возвращает healthy после восстановления worker/scheduler и выхода из maintenance
- Daily logs: `storage/logs/laravel-YYYY-MM-DD.log`, `twilio-YYYY-MM-DD.log`, `registration-YYYY-MM-DD.log`; `LOG_DAILY_DAYS=14` (default); старые/emergency логи обслуживает server logrotate
- На staging/server проверены HTTP smoke, integrations и restore drill в изолированных БД и storage

## LOCAL/CI acceptance (до production install --no-dev)

- PHP tests проходят с dev dependencies и отдельной разрешённой test DB
- `composer audit`, `npm ci`, `npm run build`, `npm audit` проходят; npm test script сейчас отсутствует
- Production не требует `php artisan test`/PHPUnit и не использует локальный test DB guard
