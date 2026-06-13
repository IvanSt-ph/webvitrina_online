# Local Backup

Локальный backup WebVitrina создаёт два архива и checksum-файл:

- `database.sql.gz` — дамп MySQL базы из `.env`;
- `storage-public.tar.gz` — архив `storage/app/public`;
- `SHA256SUMS` — контрольные суммы для проверки целостности.

## Создать backup вручную

```powershell
php artisan backup:run
```

## Проверить свежесть и целостность

```powershell
php artisan backup:health-check
```

## Где лежат backup

Локально путь задан в `.env`:

```env
BACKUP_DIR=C:/webvitrina_online/storage/app/private/backups
BACKUP_KEEP_DAYS=14
BACKUP_DAILY_AT=03:15
```

Каждый запуск создаёт отдельную папку вида `20260608-211719`.

## Автоматический запуск

Laravel schedule уже содержит ежедневный backup в `03:15`.
Чтобы он реально запускался, на сервере или локальной машине должен работать внешний планировщик:

```powershell
php artisan schedule:run
```

Для Windows это лучше повесить в Task Scheduler раз в минуту. Для Linux/VPS — в cron:

```cron
* * * * * cd /var/www/webvitrina && php artisan schedule:run >> /dev/null 2>&1
```

## Проверка восстановления

Периодически проверяйте backup на отдельной тестовой базе, не на рабочей:

```powershell
mysql -u root -e "DROP DATABASE IF EXISTS webvitrina_restore_check; CREATE DATABASE webvitrina_restore_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "gzip -dc C:\path\to\backup\database.sql.gz | mysql -u root webvitrina_restore_check"
```

После восстановления проверьте количество пользователей, товаров и заказов.
