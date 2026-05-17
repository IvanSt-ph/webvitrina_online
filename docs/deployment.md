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
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
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

## Логи

Основные файлы логов:

```text
storage/logs/laravel.log
storage/logs/twilio.log
storage/logs/registration.log
```

Если пользователь видит сообщение `Не удалось отправить SMS. Попробуйте позже.`, реальную причину смотри в `storage/logs/twilio.log`.
