# Local Backup

Локальный backup WebVitrina создаёт три архива, манифест и checksum-файл:

- `database.sql.gz` — дамп MySQL базы из `.env`;
- `storage-public.tar.gz` — архив `storage/app/public`;
- `storage-private-chat-images.tar.gz` — только приватные вложения чатов из `storage/app/private/chat-images`;
- `manifest.json` — дата создания, имя базы и количество записей основных таблиц;
- `SHA256SUMS` — контрольные суммы для проверки целостности.

## Согласованность базы (PROD-03)

SQL-дамп и `row_counts` манифеста читаются через одно отдельное write-соединение PDO
в транзакции `REPEATABLE READ`, начатой командой
`START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY` до чтения таблиц.
Backup принимает только InnoDB: при обнаружении другого engine завершится ошибкой,
удалит временную папку и не опубликует backup. При ошибке чтения snapshot откатывается;
при успехе транзакция завершается до сжатия и архивации файлов.

Обычные INSERT/UPDATE/DELETE продолжают работать: dump видит зафиксированные данные
на момент начала snapshot. Чужая транзакция приложения не используется и не завершается.
`SHOW CREATE TABLE` выполняется на том же PDO; DDL и SET из SQL-файла только записываются
в файл, а не исполняются на сервере. Внешний mysqldump не требуется.

Не запускайте миграции и другой DDL (CREATE/ALTER/DROP/RENAME/TRUNCATE) одновременно
с backup: схема и список таблиц не версионируются MVCC. Backup удерживает metadata locks
на перечисленных таблицах до конца транзакции; DDL может ожидать их освобождения.
Длительный snapshot удерживает старые версии строк InnoDB и создаёт нагрузку на undo.
Текущий экспорт сохраняет порции INSERT по 100 строк и стандартную буферизацию PDO;
оптимизация больших баз не входит в PROD-03. Архивы файлов создаются после SQL-дампа
и не являются атомарным снимком вместе с БД.

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

Для Windows используйте готовые скрипты из `tools`; установка и проверка описаны в `docs/local-background-services.md`. Для Linux/VPS — cron:

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

Файлы storage восстанавливаются только из полного backup с корректными SHA256:

```powershell
php artisan backup:restore-files C:\path\to\backup\20260923-030000 --force
```

Команда предварительно распаковывает оба архива во временный каталог, затем заменяет `storage/app/public` и только `storage/app/private/chat-images`. Каталог private backups, cache и logs она не затрагивает. Старые backup без private archive отклоняются как неполные.
