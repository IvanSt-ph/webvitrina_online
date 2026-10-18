# WebVitrina

WebVitrina — многостраничный маркетплейс на Laravel с ролями покупателя,
продавца и администратора. На текущем этапе приложение работает как агрегатор
товаров с самовывозом: заказ оформляется без онлайн-списания, а оплату продавец
подтверждает при выдаче.

## Возможности

- каталог, поиск, категории, атрибуты и рекомендации;
- корзина, избранное и оформление pickup-заказов;
- кабинеты покупателя, продавца и администратора;
- управление товарами, остатками, заказами, отзывами и спорами;
- чаты и приватные вложения;
- очереди, scheduler, operational health и резервное копирование;
- Blade, Tailwind CSS, Alpine.js и Vite.

## Требования

Точные ограничения задают lock-файлы и манифесты проекта:

- PHP `^8.4.1`;
- Laravel `^12.0`;
- MySQL 8 с InnoDB;
- Composer;
- Node.js `^20.19.0 || >=22.12.0` и npm;
- PHP-расширения, необходимые Laravel, MySQL, GD/WebP и обработке файлов.

## Локальная установка

```bash
git clone https://github.com/IvanSt-ph/webvitrina.git
cd webvitrina
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Настройте отдельную локальную базу в `.env`, затем выполните миграции и создайте
ссылку на публичное хранилище:

```bash
php artisan migrate
php artisan storage:link
npm run build
php artisan serve
```

Для постоянного локального worker и scheduler в Windows используется
[инструкция по фоновым процессам](docs/local-background-services.md).

## Документация

- [Production release runbook](docs/release-runbook.md) — подготовка, deployment,
  rollback и финальная приёмка.
- [Production monitoring](docs/production-monitoring.md) — health checks, worker,
  scheduler и доставка тревог.
- [Backup and restore safety](docs/backup-restore-safety.md) — формат backup v3,
  блокировка записей и восстановление.
- [Domain integrity](docs/domain-integrity.md) — ключевые инварианты заказов,
  аккаунтов, каталога и файлов.
- [Production readiness](docs/production-readiness-audit.md) — актуальный остаток
  работ перед запуском и границы статической проверки.

Исторические аудиты и отчёты завершённых этапов находятся в `docs/archive/` и
не являются описанием текущего состояния проекта.

## Проверки

PHP-тесты настроены на выделенную MySQL-базу `webv3_testing`. Защитные проверки
в `tests/TestCase.php` и `tests/Support` намеренно отвергают другую базу или
учётную запись. Перед запуском тестов проверьте `.env.testing`; production-базу
для тестов использовать нельзя.

```bash
php artisan test
composer audit
npm run build
npm audit
```

Production deployment выполняется только по release runbook. На сервере после
`composer install --no-dev` PHPUnit не запускается.
