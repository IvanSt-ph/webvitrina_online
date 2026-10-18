# WebVitrina: backup v3 и безопасное восстановление

Этот документ описывает действующий формат резервной копии, границу согласованности
и восстановление. Порядок остановки writers и deployment находится в
[release runbook](release-runbook.md).

## Формат v3

`php artisan backup:run` публикует завершённый каталог только после создания и
проверки всех компонентов:

- `database.sql.gz` — согласованный MySQL snapshot;
- `storage-public.tar.gz` — `storage/app/public`;
- `storage-private-chat-images.tar.gz` — приватные вложения чатов;
- `storage-private-review-images.tar.gz` — приватные изображения отзывов;
- `manifest.json` — format v3, метаданные и статистика snapshot;
- `SHA256SUMS` — контрольные суммы всех перечисленных файлов.

Старые v2 backup могут оставаться историческими копиями, но текущий health и
production recovery должны использовать полный v3. Checksums подтверждают
целостность байтов, а не самостоятельную восстановимость приложения.

## Создание и проверка

Настройки:

```env
BACKUP_DIR=/var/backups/webvitrina
BACKUP_DAILY_AT=03:15
BACKUP_MAX_AGE_HOURS=30
BACKUP_KEEP_DAYS=14
```

Ручной запуск и проверка:

```bash
php artisan backup:run
php artisan backup:health-check --max-age-hours=30
```

Команда уже запланирована в `routes/console.php`. Внешний cron запускает Laravel
scheduler каждую минуту; отдельный ежедневный cron для backup создаст дубль.

Backup-каталог должен находиться вне document root, иметь доступ только у
оператора и процессов приложения и копироваться на независимый сервер/storage.
Одна копия на том же диске не защищает от потери узла. Отдельно сохраняются
стабильный `APP_KEY`, необходимые secrets/config и совместимая ревизия кода.

## Согласованность DB и файлов

Backup открывает отдельное непостоянное MySQL-соединение, устанавливает
`REPEATABLE READ`, начинает `START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY`,
проверяет InnoDB и читает schema, строки и row counts из одного snapshot. DDL,
миграции, `TRUNCATE` и внешние SQL-writers во время backup запрещены.

Перед snapshot команда получает exclusive `BackupWriteBarrier`. Это process-owned
`flock` на стабильном inode `storage/framework/backup-writes.lock`. HTTP mutations,
поддерживаемые service mutations, jobs, account cleanup и writable health probes
используют shared lock. Exclusive lock удерживается до завершения архивов,
manifest, checksums и публикации каталога через rename.

Lock-файл нельзя удалять, ротировать или размещать отдельно в каждом release.
Все PHP-процессы должны видеть один inode с рабочей семантикой `flock`. Модель не
поддерживает независимые upload volumes/hosts с отдельными locks и не предполагает
надёжность NFS locking.

Кооперативная блокировка не охватывает:

- raw SQL, migrations, seeders и Tinker;
- внешние скрипты и прямое изменение файлов;
- writers, не использующие приложение;
- новый код, который обходит middleware/service boundary.

Эти операции требуют остановки writers. GET/HEAD browsing остаётся доступным,
пока обработчики не меняют DB-ссылки или backup-файлы.

## Покрытые mutations

Текущий barrier охватывает product images/gallery/purge, avatar и shop banner,
category/review/chat/admin banner files, account cleanup, queue jobs, writable
health probes и activation restore. Низкоуровневые ImageService, ChatImageService
и model hooks сами по себе не являются универсальной транзакционной границей:
новый вызывающий код обязан защитить всю DB+filesystem операцию.

Файловая компенсация внутри конкретного mutation остаётся необходимой. Barrier
не исправляет F08/F09: параллельные обычные writers могут одновременно держать
shared lock, а локальная DB/file операция должна сама обеспечивать rollback.

## Ошибки и публикация

DB/public/private/manifest/hash failure не публикует backup. При возможности
принадлежащий команде `.tmp` удаляется; после аварийного завершения оставшийся
временный каталог требует осмотра. Существующий каталог назначения никогда не
удаляется для разрешения collision. Retention failure после успешной публикации
сообщает ошибку, но не уничтожает новую копию.

## Isolated restore drill

Restore необходимо проверить до production-запуска. Используйте отдельного DB user
без прав на production DB, отдельную базу и новый пустой приватный каталог:

```bash
DRILL_DIR=$(mktemp -d /tmp/webvitrina-restore-check.XXXXXX)
php artisan backup:restore-files /absolute/path/to/completed-backup --drill="$DRILL_DIR"
```

Команда проверяет manifest, SHA256 и архивы, затем создаёт фиксированные корни:

- `$DRILL_DIR/public`;
- `$DRILL_DIR/private/chat-images`;
- `$DRILL_DIR/private/review-images`.

SQL восстанавливается отдельно в изолированную базу. Приложение для проверки
должно использовать отдельные storage, cache, session и queue, а внешние отправки
должны быть отключены. Успешно проверить минимум schema/migrations, количества
основных записей, ссылки на файлы и открытие выборочных public/private объектов.

Restore destination отклоняет относительные и пустые пути, filesystem/project root,
live roots и их предков/потомков, пересечение с backup source, symlink/junction
aliases и непустой drill-каталог. `--drill` и `--force` взаимоисключающие.

### Локальная Windows-проверка

Локально допустим защищённый путь вроде:

```env
BACKUP_DIR=C:/webvitrina_online/storage/app/private/backups
```

Для регулярного worker/scheduler используйте [локальные фоновые процессы](local-background-services.md).
Создание и health-check выполняются теми же Artisan-командами, что и на сервере.
Не используйте root/admin DB account для restore drill.

## Production restore

`php artisan backup:restore-files /absolute/backup --force` заменяет только файловые
корни. SQL import — отдельная операция. На всё время согласованного DB+files restore:

1. остановите HTTP writers, workers, scheduler и внешние writers;
2. проверьте manifest/SHA256 и выбранную ревизию backup;
3. восстановите SQL и все три файловых root из одной копии;
4. проверьте DB/file соответствие до возобновления процессов;
5. обновите caches, запустите worker/scheduler, снимите maintenance и выполните
   health/smoke checks.

Перед activation новые деревья полностью извлекаются и проверяются. Старые roots
перемещаются в recovery staging, после чего новые ставятся на место. Каждая операция
rename и rollback проверяется отдельно. При неполном rollback staging не удаляется:
writers остаются остановленными, оператор восстанавливает указанные previous copies
и сверяет их с DB. Нельзя автоматически удалять recovery artifacts после ошибки.

Process/host death между несколькими filesystem rename не является атомарной
операцией для трёх каталогов. Поэтому off-host copy и фактический restore drill —
обязательная часть release acceptance, а не необязательная рекомендация.
