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
- `QUEUE_CONNECTION=database` или другой рабочий queue driver, не `sync`
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
- `php artisan queue:failed` не показывает неразобранные production-ошибки
- Cron/systemd timer для Laravel scheduler настроен: `php artisan schedule:run` каждую минуту
- Ежедневный backup БД и `storage/app/public` настроен по примеру `deploy/backup-webvitrina.sh.example`
- `BACKUP_DIR` и `BACKUP_MAX_AGE_HOURS` заданы так, чтобы админский релиз-чеклист видел свежий backup
- Если backup запускается через Laravel scheduler, задан `BACKUP_COMMAND` и включён cron `php artisan schedule:run`
- В backup есть `database.sql.gz`, `storage-public.tar.gz` и `SHA256SUMS`
- `php artisan backup:health-check --max-age-hours=30` проходит без ошибок
- Restore backup проверен на тестовой базе, не только создание архива
- Выполнены `php artisan migrate --force`, `php artisan storage:link`
- После деплоя выполнены `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`, `php artisan queue:restart`
- Веб-сервер отдаёт сайт только по HTTPS, а приложение возвращает HSTS-заголовок в production
- `composer audit` не показывает advisories
- `npm audit --omit=dev` не показывает production vulnerabilities
- `php artisan test` проходит
