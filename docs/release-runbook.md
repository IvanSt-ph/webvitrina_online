# WebVitrina Release Runbook

Короткий порядок перед публичным запуском. Выполняется на боевом сервере после загрузки кода и настройки `.env`.

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
MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=noreply@your-domain.example
BACKUP_DIR=/var/backups/webvitrina
BACKUP_MAX_AGE_HOURS=30
```

`APP_KEY` должен быть заполнен один раз и не должен меняться между деплоями.

## 2. Установить зависимости и собрать frontend

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 3. Применить миграции и production cache

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Если `storage:link` пишет, что ссылка уже есть, это нормально. Важно, чтобы `/public/storage` реально вёл в `storage/app/public`.

## 4. Включить очередь

Worker нельзя держать открытым терминалом. Используйте Supervisor/systemd/панель хостинга.

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

`queue:health-check` должен пройти, `queue:failed` должен быть пустым или содержать только разобранные старые ошибки.

## 5. Настроить backup

Backup должен включать БД и `storage/app/public`.

Скрипт-пример:

```text
deploy/backup-webvitrina.sh.example
```

Проверка:

```bash
php artisan backup:health-check --max-age-hours=30
```

После первого backup обязательно проверить восстановление на отдельной тестовой базе.

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
composer audit
npm audit --omit=dev
php artisan queue:health-check --timeout=15
php artisan backup:health-check --max-age-hours=30
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

## 10. После релиза

Первые сутки смотреть:

- `storage/logs/laravel.log`;
- failed jobs;
- свежесть backup;
- реальные письма;
- жалобы пользователей на checkout и чат.
