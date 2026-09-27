# Локальные фоновые процессы

Для локальной Windows-машины проект содержит две задачи Task Scheduler:

- `WebVitrina-Local-Worker` постоянно обрабатывает очередь `database` и перезапускает worker после штатного часового обновления процесса;
- `WebVitrina-Local-Scheduler` запускает `php artisan schedule:run` каждую минуту и раз в час проверяет свежесть backup. Если свежей корректной копии нет, создаётся новая.

Обе задачи работают с ограниченными правами и только пока текущий пользователь вошёл в Windows. Это локальная настройка разработки; на production нужны Supervisor/systemd и cron из `docs/deployment.md`.

## Установка

Из корня проекта в PowerShell:

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\install-local-background.ps1
```

По умолчанию используется `php` из `PATH`. Другой исполняемый файл можно передать через `-PhpPath`.

## Проверка

```powershell
Get-ScheduledTask -TaskName WebVitrina-Local-Worker,WebVitrina-Local-Scheduler
php artisan queue:health-check --timeout=15
php artisan backup:health-check --max-age-hours=30
```

Логи создаются в `storage/logs/local-worker-YYYY-MM-DD.log` и `storage/logs/local-scheduler-YYYY-MM-DD.log`.

## Удаление

```powershell
pwsh -NoProfile -ExecutionPolicy Bypass -File .\tools\uninstall-local-background.ps1
```

Скрипт удаляет только задачи, которые ссылаются на runner текущей рабочей копии.
