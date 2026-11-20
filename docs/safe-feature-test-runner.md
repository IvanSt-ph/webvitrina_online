# Защищённый запуск Feature-тестов

Подготовлен для отдельного разрешения на SQL-записи. Сейчас исправления проверены
только lint, чистыми Unit-тестами, файловыми проверками и PHP-процессами без БД.
Исправление bootstrap не означает, что CredentialMutationConcurrencyTest уже прошёл.

Использовать `scripts/run-safe-feature-tests.php`, а не Invoke-WvTarget из личного
PowerShell-профиля: старая функция безусловно устанавливает RefreshDatabaseState::$migrated.
Профиль не изменялся. Новый CLI не устанавливает этот флаг. Его выставляет только
ExistingSchemaMode::prepare после штатных защит и очистки.

Без `--run` CLI только проверяет выбор режима/файла/окружения и печатает план:
не читает credentials, не подключается к MySQL, не запускает тесты.
Один вызов — один проверенный файл. Произвольные suite, директории и PHPUnit-аргументы
не принимаются; допустим дополнительный `--filter=имя_теста`.

## Два несовместимых режима

- `existing-schema`: устанавливает WV_EXISTING_SCHEMA=1, допускает только allowlist
  ExistingSchemaMode. Перед тестами очищает фикстуры через TRUNCATE, сохраняет таблицу
  migrations. Возможны реальные COMMIT и дочерние процессы. Требует явного
  `--ack-cleanup=webv3_testing`. Очищаемые таблицы не должны содержать нужные данные.
- `attribute-rollback`: WV_EXISTING_SCHEMA отсутствует, разрешены только
  ProductAttributeValidationTest и CategoryAttributeImportTest. Использует их
  существующие транзакции с откатом, без глобальной очистки. SQL-защита и файловая
  изоляция установлены независимо от включения очистки.

Оба режима получают процессную блокировку параллельного запуска. Дочерние workers
наследуют единственный scope родителя; не создают новый и не очищают таблицы.
Наследованные storage/view/cache-пути проверяются до LoadConfiguration, затем
подключается тот же narrow absolute-cache prefix и изолируются диски, логи, view,
file-cache и backup lock. Повреждённые/чужие/связанные пути отвергаются.
Артефакты остаются под storage/framework/testing; автоматического удаления нет.

## Подключение и окружение

Запускать из чистого терминала, предпочтительно `pwsh -NoProfile`. Credentials
читаются только из существующего `.env.testing`; fallback к `.env` отсутствует.
Файл не создаётся и не меняется. Требуются APP_ENV=testing, DB_CONNECTION=mysql,
webv3_testing / webvitrina_test на 127.127.126.9:3306.
DB_URL, DB_SOCKET, конфликтующие процессные настройки и WV_ALLOW_ATTRIBUTE_SEEDER_TEST
отклоняются. Нельзя использовать webv3_attributes_test или webv3.

До PHPUnit проверяются metadata и фактический write-PDO: SELECT DATABASE() и
CURRENT_USER() = webvitrina_test@localhost. Preflight-соединение закрывается.
Далее сохраняются штатные TestDatabaseSafety, connector/SQL guards, запреты DDL,
миграций и сидеров, проверка посторонних сессий перед очисткой. Переменные задаются
только внутри PHP-процесса и наследуются детьми; профиль/системное окружение не меняются.
Группа attribute-seeder всегда исключена. PHPUnit exit code возвращается без подмены.

## Команды: ТОЛЬКО после отдельного разрешения на SQL-записи/очистку

Из корня проекта, последовательно, останавливаясь на ошибке:

```powershell
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/ProductMutationConcurrencyTest.php --run --ack-cleanup=webv3_testing
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/ReviewModerationConcurrencyTest.php --run --ack-cleanup=webv3_testing
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/Auth/CredentialMutationConcurrencyTest.php --run --ack-cleanup=webv3_testing
php scripts/run-safe-feature-tests.php --mode=attribute-rollback --file=tests/Feature/ProductAttributeValidationTest.php --run
```

Для безопасного просмотра убрать `--run` и `--ack-cleanup=webv3_testing`.
До разрешения на запись выполнять только такой просмотр и Unit-тесты.
