<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ProductionHealth
{
    public static function make(): array
    {
        $database = self::database();
        $disk = self::disk();
        $logSize = self::logSize();
        $backup = self::backup();
        $queue = self::queue();
        $mail = self::mail();
        $storage = self::storage();
        $sitemap = self::route('sitemap', '/sitemap.xml');
        $robots = self::route('robots', '/robots.txt');
        $errors = self::recentErrors();

        $checks = [
            [
                'group' => 'Окружение',
                'items' => [
                    ['label' => 'APP_ENV=production', 'ok' => app()->environment('production'), 'current' => config('app.env'), 'hint' => 'На боевом сервере окружение должно быть production.'],
                    ['label' => 'APP_DEBUG=false', 'ok' => ! config('app.debug'), 'current' => config('app.debug') ? 'true' : 'false', 'hint' => 'Debug-страницы могут раскрыть стек, SQL и переменные окружения.'],
                    ['label' => 'HTTPS-cookie', 'ok' => (bool) config('session.secure'), 'current' => config('session.secure') ? 'true' : 'false', 'hint' => 'Для HTTPS включите SESSION_SECURE_COOKIE=true.'],
                    ['label' => 'Очереди не sync', 'ok' => $queue['ok'], 'current' => $queue['value'], 'hint' => $queue['detail']],
                ],
            ],
            [
                'group' => 'Инфраструктура',
                'items' => [
                    ['label' => 'База данных доступна', 'ok' => $database['ok'], 'current' => $database['value'], 'hint' => $database['detail']],
                    ['label' => 'Свободное место на диске', 'ok' => $disk['ok'], 'current' => $disk['value'], 'hint' => $disk['detail']],
                    ['label' => 'Размер laravel.log под контролем', 'ok' => $logSize['ok'], 'current' => $logSize['value'], 'hint' => $logSize['detail']],
                    ['label' => 'Почта настроена', 'ok' => $mail['ok'], 'current' => $mail['value'], 'hint' => $mail['detail']],
                    ['label' => 'Хранилище связано', 'ok' => $storage['ok'], 'current' => $storage['value'], 'hint' => $storage['detail']],
                    ['label' => 'Sitemap доступен', 'ok' => $sitemap['ok'], 'current' => $sitemap['value'], 'hint' => $sitemap['detail']],
                    ['label' => 'Robots доступен', 'ok' => $robots['ok'], 'current' => $robots['value'], 'hint' => $robots['detail']],
                    ['label' => 'Scheduler включён', 'ok' => false, 'current' => 'проверяется вручную', 'hint' => 'На сервере cron/systemd должен запускать php artisan schedule:run каждую минуту.'],
                    ['label' => 'Бэкапы БД и файлов свежие', 'ok' => $backup['ok'], 'current' => $backup['value'], 'hint' => $backup['detail']],
                    ['label' => 'Ошибок за 24 часа нет', 'ok' => $errors['ok'], 'current' => $errors['value'], 'hint' => $errors['detail']],
                ],
            ],
            [
                'group' => 'Бизнес-логика',
                'items' => [
                    ['label' => 'Онлайн-оплата честно выключена', 'ok' => true, 'current' => 'режим договорённости', 'hint' => 'До эквайринга сайт не должен обещать списание с карты.'],
                    ['label' => 'Доставка описана как договорённость', 'ok' => true, 'current' => 'по продавцам', 'hint' => 'До логистики показывайте отдельные условия по продавцам.'],
                    ['label' => 'Правила опубликованы', 'ok' => true, 'current' => '/rules, /privacy, /delivery-returns', 'hint' => 'Финальную редакцию всё равно стоит показать юристу.'],
                    ['label' => 'Модерация жалоб включена', 'ok' => true, 'current' => 'товары блокируются админом', 'hint' => 'Продавец не может сам вернуть заблокированный товар.'],
                ],
            ],
        ];

        $flat = collect($checks)->flatMap(fn ($group) => $group['items']);

        return [
            'cards' => [
                self::card('База данных', $database),
                self::card('Диск', $disk),
                self::card('laravel.log', $logSize),
                self::card('Очередь', $queue),
                self::card('Storage', $storage),
                self::card('Backup', $backup),
                self::card('SMTP', $mail),
                self::card('Sitemap', $sitemap),
                self::card('Robots', $robots),
                self::card('Ошибки 24ч', $errors),
            ],
            'checks' => $checks,
            'done' => $flat->where('ok', true)->count(),
            'total' => $flat->count(),
        ];
    }

    private static function card(string $title, array $check): array
    {
        return [
            'title' => $title,
            'ok' => $check['ok'],
            'value' => $check['value'],
            'detail' => $check['detail'],
            'icon' => $check['icon'] ?? 'ri-pulse-line',
        ];
    }

    private static function backup(): array
    {
        $backup = BackupHealth::latest();

        return [
            'ok' => $backup['ok'],
            'value' => $backup['value'],
            'detail' => $backup['ok'] || $backup['latest'] === null
                ? $backup['detail']
                : $backup['detail'] . ' Проблемы: ' . implode(' ', $backup['issues']),
            'icon' => 'ri-database-2-line',
        ];
    }

    private static function database(): array
    {
        $started = microtime(true);

        try {
            DB::select('select 1');
            $milliseconds = (int) round((microtime(true) - $started) * 1000);

            return [
                'ok' => true,
                'value' => $milliseconds . 'ms',
                'detail' => 'База данных доступна. Время ответа на простой запрос: ' . $milliseconds . 'ms.',
                'icon' => 'ri-server-line',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'value' => 'нет ответа',
                'detail' => 'Ошибка подключения к БД: ' . $e->getMessage(),
                'icon' => 'ri-server-line',
            ];
        }
    }

    private static function disk(): array
    {
        $path = base_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $freeBytes = is_int($free) || is_float($free) ? (float) $free : null;
        $totalBytes = is_int($total) || is_float($total) ? (float) $total : null;
        $minimumFreeBytes = 1024 * 1024 * 1024;
        $ok = $freeBytes !== null && $freeBytes >= $minimumFreeBytes;

        return [
            'ok' => $ok,
            'value' => $freeBytes !== null ? 'Свободно: ' . self::formatBytes($freeBytes) : 'неизвестно',
            'detail' => $totalBytes !== null
                ? 'Всего на разделе: ' . self::formatBytes($totalBytes) . '. Предупреждение ниже 1 GB свободного места.'
                : 'Не удалось определить размер диска для ' . $path,
            'icon' => 'ri-hard-drive-3-line',
        ];
    }

    private static function logSize(): array
    {
        $logPath = storage_path('logs/laravel.log');
        $warningBytes = 100 * 1024 * 1024;
        $criticalBytes = 500 * 1024 * 1024;

        if (! is_file($logPath)) {
            return [
                'ok' => true,
                'value' => '0 B',
                'detail' => 'Файл laravel.log пока не найден.',
                'icon' => 'ri-file-list-3-line',
            ];
        }

        $size = filesize($logPath) ?: 0;
        $ok = $size < $warningBytes;

        return [
            'ok' => $ok,
            'value' => 'Размер: ' . self::formatBytes($size),
            'detail' => $size >= $criticalBytes
                ? 'Лог очень большой. Нужна ротация логов, иначе диск может закончиться.'
                : 'Предупреждение после 100 MB. Настройте logrotate или daily channel перед релизом.',
            'icon' => 'ri-file-list-3-line',
        ];
    }

    private static function queue(): array
    {
        $connection = (string) config('queue.default');
        $failed = self::tableCount(config('queue.failed.table', 'failed_jobs'));
        $jobs = self::tableCount(config("queue.connections.{$connection}.table", 'jobs'));
        $ok = $connection !== 'sync' && ($failed === null || $failed === 0);

        return [
            'ok' => $ok,
            'value' => $connection . ($failed !== null ? ', failed: ' . $failed : ''),
            'detail' => $jobs !== null
                ? 'В очереди сейчас задач: ' . $jobs . '. Worker должен быть запущен на сервере.'
                : 'Проверьте, что queue worker запущен на сервере.',
            'icon' => 'ri-stack-line',
        ];
    }

    private static function mail(): array
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$mailer}.transport", $mailer);
        $from = (string) config('mail.from.address');
        $host = (string) config("mail.mailers.{$mailer}.host");
        $ok = ! in_array($transport, ['log', 'array'], true)
            && filter_var($from, FILTER_VALIDATE_EMAIL)
            && ($transport !== 'smtp' || $host !== '');

        return [
            'ok' => $ok,
            'value' => $mailer . ' / ' . $transport,
            'detail' => $ok
                ? 'Конфигурация почты заполнена. Перед релизом отправьте тестовое письмо вручную.'
                : 'Для продакшена не используйте log/array и заполните MAIL_FROM_ADDRESS/SMTP.',
            'icon' => 'ri-mail-check-line',
        ];
    }

    private static function storage(): array
    {
        $path = public_path('storage');
        $target = storage_path('app/public');

        clearstatcache(true, $path);
        clearstatcache(true, $target);

        $linkedTarget = file_exists($path) ? readlink($path) : false;
        $isLinked = is_link($path) || $linkedTarget !== false;
        $isUsable = file_exists($path) && (is_dir($path) || $isLinked);
        $pointsToStorage = $linkedTarget === false
            || self::normalizePath((string) $linkedTarget) === self::normalizePath($target)
            || (realpath((string) $linkedTarget) !== false && realpath((string) $linkedTarget) === realpath($target));
        $ok = $isUsable && $pointsToStorage;

        return [
            'ok' => $ok,
            'value' => $ok ? ($isLinked ? 'linked' : 'directory') : 'нет ссылки',
            'detail' => $ok
                ? $path . ($linkedTarget !== false ? ' -> ' . $linkedTarget : '')
                : 'На сервере выполните php artisan storage:link.',
            'icon' => 'ri-folder-shield-2-line',
        ];
    }

    private static function route(string $name, string $expectedPath): array
    {
        $exists = Route::has($name);
        $path = $exists ? route($name, [], false) : 'route not found';

        return [
            'ok' => $exists && $path === $expectedPath,
            'value' => $path,
            'detail' => $exists ? 'Проверьте доступность по реальному домену после деплоя.' : 'Маршрут не зарегистрирован.',
            'icon' => $name === 'robots' ? 'ri-robot-2-line' : 'ri-road-map-line',
        ];
    }

    private static function recentErrors(): array
    {
        $logPath = storage_path('logs/laravel.log');
        $count = 0;

        if (is_file($logPath) && is_readable($logPath)) {
            $size = filesize($logPath) ?: 0;
            $handle = fopen($logPath, 'rb');

            if ($handle) {
                if ($size > 1024 * 1024) {
                    fseek($handle, -1024 * 1024, SEEK_END);
                }

                $chunk = stream_get_contents($handle) ?: '';
                fclose($handle);

                $count = collect(preg_split('/\r\n|\r|\n/', $chunk))
                    ->filter(fn ($line) => self::isRecentErrorLine($line))
                    ->count();
            }
        }

        return [
            'ok' => $count === 0,
            'value' => $count . ' записей',
            'detail' => is_file($logPath)
                ? 'Считаются ERROR/CRITICAL/ALERT/EMERGENCY в последнем фрагменте laravel.log за 24 часа.'
                : 'Файл laravel.log пока не найден.',
            'icon' => 'ri-bug-line',
        ];
    }

    private static function isRecentErrorLine(string $line): bool
    {
        if (! preg_match('/\[(?<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*\.(ERROR|CRITICAL|ALERT|EMERGENCY):/i', $line, $matches)) {
            return false;
        }

        $timestamp = strtotime($matches['date']);

        return $timestamp !== false && $timestamp >= now()->subDay()->timestamp;
    }

    private static function tableCount(?string $table): ?int
    {
        if (! $table || ! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->count();
    }

    private static function formatBytes(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = max(0, (float) $bytes);
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        $precision = $unit === 0 ? 0 : 1;

        return number_format($size, $precision, '.', ' ') . ' ' . $units[$unit];
    }

    private static function normalizePath(string $path): string
    {
        return rtrim(strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path)), DIRECTORY_SEPARATOR);
    }
}
