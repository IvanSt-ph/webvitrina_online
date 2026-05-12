# Production Checklist

Перед запуском на продакшне проверь:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.example`
- `APP_KEY` сгенерирован командой `php artisan key:generate`
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
- Выполнены `php artisan migrate --force`, `php artisan storage:link`
- После деплоя выполнены `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`
- `composer audit` не показывает high/critical advisories
- `npm audit --omit=dev` не показывает production vulnerabilities
- `php artisan test` проходит
