<?php

namespace App\Support;

use App\Jobs\QueueHealthCheckJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ProductionHealth
{
    public const PASS = 'pass';

    public const FAIL = 'fail';

    public const NOT_CHECKED = 'not_checked';

    public const WARNING = 'warning';

    public const SCHEDULER_CACHE_KEY = 'production-health:scheduler-last-run';

    private const QUEUE_HEALTH_MAX_AGE_MINUTES = 15;

    private const MAX_LOG_BYTES_PER_FILE = 1048576;

    public static function make(): array
    {
        $database = self::database();
        $disk = self::disk();
        $logSize = self::logSize();
        $backup = self::backup();
        $queue = self::queue($database['ok']);
        $mail = self::mail();
        $storage = self::storage();
        $sitemap = self::route('sitemap', '/sitemap.xml');
        $robots = self::route('robots', '/robots.txt');
        $errors = self::recentErrors();

        $checks = [
            [
                'group' => 'Окружение',
                'items' => [
                    self::item('APP_ENV=production', app()->environment('production') ? self::PASS : self::FAIL, config('app.env'), 'На боевом сервере окружение должно быть production.'),
                    self::item('APP_DEBUG=false', ! config('app.debug') ? self::PASS : self::FAIL, config('app.debug') ? 'true' : 'false', 'Debug-страницы могут раскрыть стек, SQL и переменные окружения.'),
                    self::item('HTTPS-cookie', config('session.secure') ? self::PASS : self::FAIL, config('session.secure') ? 'true' : 'false', 'Для HTTPS включите SESSION_SECURE_COOKIE=true.'),
                    self::item('Worker очереди работает', $queue['status'], $queue['value'], $queue['detail']),
                ],
            ],
            [
                'group' => 'Инфраструктура',
                'items' => [
                    self::itemFromCheck('База данных доступна', $database),
                    self::itemFromCheck('Свободное место на диске', $disk),
                    self::itemFromCheck('Размер Laravel-логов под контролем', $logSize),
                    self::itemFromCheck('Почта настроена', $mail),
                    self::itemFromCheck('Хранилище связано', $storage),
                    self::itemFromCheck('Sitemap доступен', $sitemap),
                    self::itemFromCheck('Robots доступен', $robots),
                    self::itemFromCheck('Scheduler включён', self::scheduler()),
                    self::itemFromCheck('Failed jobs отсутствуют', self::failedJobs()),
                    self::itemFromCheck('Рабочие каталоги доступны для записи', self::writable()),
                    self::itemFromCheck('Бэкапы БД и файлов свежие', $backup),
                    self::itemFromCheck('Ошибок за 24 часа нет', $errors),
                ],
            ],
            [
                'group' => 'Бизнес-логика',
                'items' => [
                    self::item('Онлайн-оплата честно выключена', self::NOT_CHECKED, 'не проверено', 'Вручную подтвердите, что сайт не обещает списание с карты до подключения эквайринга.'),
                    self::item('Доставка описана как договорённость', self::NOT_CHECKED, 'не проверено', 'Вручную проверьте отображаемые условия доставки по продавцам.'),
                    self::item('Правила опубликованы', self::NOT_CHECKED, 'не проверено', 'Вручную проверьте финальную редакцию /rules, /privacy и /delivery-returns.'),
                    self::item('Модерация жалоб включена', self::NOT_CHECKED, 'не проверено', 'Вручную проверьте блокировку товара и невозможность самостоятельной разблокировки продавцом.'),
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
            'failed' => $flat->where('status', self::FAIL)->count(),
            'not_checked' => $flat->where('status', self::NOT_CHECKED)->count(),
            'checked' => $flat->whereIn('status', [self::PASS, self::WARNING, self::FAIL])->count(),
            'total' => $flat->count(),
        ];
    }

    /** Operational checks deliberately exclude manual release/business checks. */
    public function operational(): array
    {
        $checks = [];
        foreach ([
            'application' => fn () => self::result(! app()->isDownForMaintenance(), 'Application booted; maintenance mode must be off.'),
            'database' => fn () => self::database(),
            'scheduler' => fn () => self::scheduler(),
            'queue' => function () use (&$checks) {
                return self::queue($checks['database']['ok'] ?? false);
            },
            'failed_jobs' => fn () => self::failedJobs(),
            'backup' => fn () => self::backup(),
            'disk' => fn () => self::disk(),
            'writable' => fn () => self::writable(),
            'storage_link' => fn () => self::storage(),
            'logging' => fn () => self::logging(),
            'log_size' => fn () => self::logSize(),
            'recent_errors' => fn () => self::recentErrors(),
        ] as $name => $probe) {
            try {
                $check = $probe();
                $check['status'] = self::status($check);
                if (in_array($name, ['log_size', 'recent_errors'], true) && ! $check['ok']) {
                    $check['status'] = self::WARNING;
                }
                $checks[$name] = $check;
            } catch (\Throwable) {
                $checks[$name] = self::result(false, 'Check unavailable: '.$name);
            }
        }

        return $checks;
    }

    private static function result(bool $ok, string $detail): array
    {
        return ['ok' => $ok, 'status' => $ok ? self::PASS : self::FAIL, 'value' => $ok ? 'OK' : 'unavailable', 'detail' => $detail];
    }

    private static function scheduler(): array
    {
        try {
            if (! self::persistentCache()) {
                return self::result(false, 'Scheduler heartbeat requires a shared persistent cache store.');
            }
            $value = Cache::get(self::SCHEDULER_CACHE_KEY);
            if (! is_string($value) || $value === '') {
                return self::result(false, 'Scheduler heartbeat missing; schedule:run has not been confirmed.');
            }
            $lastRun = Carbon::parse($value);
            $fresh = $lastRun->betweenIncluded(now()->subMinutes(max(1, (int) config('health.scheduler_max_age_minutes', 5))), now()->addMinute());

            return self::result($fresh, 'Scheduler last ran: '.$lastRun->toIso8601String().($fresh ? '' : ' (stale or invalid clock).'));
        } catch (\Throwable) {
            return self::result(false, 'Scheduler heartbeat unavailable or invalid.');
        }
    }

    public static function persistentCache(): bool
    {
        return in_array(config('cache.stores.'.config('cache.default').'.driver'), ['database', 'file', 'redis', 'memcached', 'dynamodb'], true);
    }

    private static function failedJobs(): array
    {
        try {
            if (! in_array(config('queue.failed.driver'), ['database', 'database-uuids'], true)) {
                return self::result(false, 'Failed job storage is disabled or unsupported by this check.');
            }
            $count = DB::connection(config('queue.failed.database'))->table(config('queue.failed.table', 'failed_jobs'))->count();

            return self::result($count === 0, 'Failed jobs: '.$count.'. Records require operator review; nothing is deleted.');
        } catch (\Throwable) {
            return self::result(false, 'Failed jobs cannot be read.');
        }
    }

    private static function writable(): array
    {
        return app(\App\Services\BackupWriteBarrier::class)->run(fn () => self::writableUnderBarrier());
    }

    private static function writableUnderBarrier(): array
    {
        $paths = [storage_path('app/public'), storage_path('app/private'), storage_path('framework/cache/data'), storage_path('framework/sessions'), storage_path('framework/views'), storage_path('logs'), base_path('bootstrap/cache'), (string) config('backup.path')];
        foreach (self::laravelLogPaths() as $logPath) {
            $paths[] = dirname($logPath);
            if (is_file($logPath) && ! is_writable($logPath)) {
                return self::result(false, 'Log file is not writable: '.$logPath);
            }
        }
        foreach (array_unique($paths) as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return self::result(false, 'Directory missing or not writable: '.$path);
            }
            // Only touch a uniquely owned probe file, never an existing log or upload.
            $probe = $path.DIRECTORY_SEPARATOR.'.health-'.bin2hex(random_bytes(12));
            try {
                if (@file_put_contents($probe, 'health', LOCK_EX) !== 6) {
                    return self::result(false, 'Cannot write to directory: '.$path);
                }
            } finally {
                if (is_file($probe)) {
                    @unlink($probe);
                }
            }
        }

        return self::result(true, 'Runtime, backup and logging directories are writable.');
    }

    private static function logging(): array
    {
        $names = [(string) config('logging.default')];
        $seen = [];
        $sinks = 0;
        while ($names !== []) {
            $name = array_pop($names);
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $driver = config("logging.channels.{$name}.driver");
            if ($driver === 'stack') {
                $children = config("logging.channels.{$name}.channels", []);
                if ($children === []) {
                    return self::result(false, 'Empty logging stack.');
                }
                $names = array_merge($names, $children);
            } elseif (! $driver || $driver === 'null'
                || config("logging.channels.{$name}.handler") === \Monolog\Handler\NullHandler::class
                || ($driver === 'daily' && (int) config("logging.channels.{$name}.days") < 1)) {
                return self::result(false, 'Logging configuration unavailable or retention is unlimited.');
            } elseif ($driver === 'single') {
                return self::result(false, 'Single log has no application rotation; configure LOG_STACK=daily.');
            } else {
                $sinks++;
            }
        }

        return self::result($sinks > 0, $sinks > 0
            ? 'Logging configured; external transports/rotation require server monitoring.'
            : 'Logging stack has no output channel.');
    }

    private static function itemFromCheck(string $label, array $check): array
    {
        return self::item($label, self::status($check), $check['value'], $check['detail']);
    }

    private static function item(string $label, string $status, mixed $current, string $hint): array
    {
        return [
            'label' => $label,
            'status' => $status,
            'ok' => $status === self::PASS,
            'current' => $current,
            'hint' => $hint,
        ];
    }

    private static function status(array $check): string
    {
        return $check['status'] ?? ($check['ok'] ? self::PASS : self::FAIL);
    }

    private static function card(string $title, array $check): array
    {
        return [
            'title' => $title,
            'ok' => $check['ok'],
            'status' => self::status($check),
            'value' => $check['value'],
            'detail' => $check['detail'],
            'icon' => $check['icon'] ?? 'ri-pulse-line',
        ];
    }

    private static function backup(): array
    {
        try {
            $backup = BackupHealth::latest();
        } catch (\Throwable) {
            return self::result(false, 'Backup validation unavailable.');
        }

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
        $status = self::PASS;
        $details = [];
        $minimum = null;
        foreach (array_unique([base_path(), storage_path(), (string) config('backup.path')]) as $path) {
            try {
                $free = app(DiskSpace::class)->free($path);
            } catch (\Throwable) {
                $free = false;
            }
            if ($free === false || $free < 0 || ! is_finite($free)) {
                $status = self::FAIL;
                $details[] = $path.': disk space unavailable';
                continue;
            }
            $minimum = $minimum === null ? $free : min($minimum, $free);
            $details[] = $path.': '.self::formatBytes($free);
            if ($free < (int) config('health.disk_critical_bytes', 268435456)) {
                $status = self::FAIL;
            } elseif ($status === self::PASS && $free < (int) config('health.disk_warning_bytes', 1073741824)) {
                $status = self::WARNING;
            }
        }

        return ['ok' => $status === self::PASS, 'status' => $status,
            'value' => 'Свободно: '.($minimum === null ? 'неизвестно' : self::formatBytes($minimum)),
            'detail' => implode('; ', $details), 'icon' => 'ri-hard-drive-3-line'];
    }

    private static function logSize(?array $logPaths = null): array
    {
        $logPaths ??= self::laravelLogPaths();
        $existingPaths = array_values(array_filter($logPaths, 'is_file'));
        $warningBytes = 100 * 1024 * 1024;
        $criticalBytes = 500 * 1024 * 1024;

        if ($existingPaths === []) {
            return [
                'ok' => true,
                'value' => '0 B',
                'detail' => 'Актуальные Laravel-логи пока не найдены.',
                'icon' => 'ri-file-list-3-line',
            ];
        }

        $size = array_sum(array_map(fn (string $path) => filesize($path) ?: 0, $existingPaths));
        $ok = $size < $warningBytes;

        return [
            'ok' => $ok,
            'value' => 'Размер: ' . self::formatBytes($size),
            'detail' => $size >= $criticalBytes
                ? 'Лог очень большой. Нужна ротация логов, иначе диск может закончиться.'
                : 'Проверено файлов: ' . count($existingPaths) . '. Предупреждение после 100 MB.',
            'icon' => 'ri-file-list-3-line',
        ];
    }

    private static function queue(bool $databaseAvailable = true): array
    {
        $connection = (string) config('queue.default');

        if ($connection === 'sync') {
            return [
                'status' => self::FAIL,
                'ok' => false,
                'value' => 'worker не используется',
                'detail' => 'QUEUE_CONNECTION=sync не подтверждает работу отдельного worker.',
                'icon' => 'ri-stack-line',
            ];
        }

        $failed = $databaseAvailable ? self::tableCount(config('queue.failed.table', 'failed_jobs')) : null;
        $jobs = $databaseAvailable ? self::tableCount(config("queue.connections.{$connection}.table", 'jobs')) : null;

        try {
            $lastSuccessValue = Cache::get(QueueHealthCheckJob::LAST_SUCCESS_CACHE_KEY);
            $lastFailureValue = Cache::get(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY);
        } catch (\Throwable) {
            return [
                'status' => self::NOT_CHECKED,
                'ok' => false,
                'value' => 'невозможно проверить',
                'detail' => 'Не удалось прочитать метку queue health-check из cache.',
                'icon' => 'ri-stack-line',
            ];
        }

        if ((! is_string($lastSuccessValue) || $lastSuccessValue === '') && is_string($lastFailureValue) && $lastFailureValue !== '') {
            return self::failedQueueResult($lastFailureValue);
        }

        if (! is_string($lastSuccessValue) || $lastSuccessValue === '') {
            return [
                'status' => self::FAIL,
                'ok' => false,
                'value' => 'worker не подтверждён',
                'detail' => 'Нет успешной проверки. Запустите php artisan queue:health-check.',
                'icon' => 'ri-stack-line',
            ];
        }

        try {
            $lastSuccess = Carbon::parse($lastSuccessValue);
        } catch (\Throwable) {
            return [
                'status' => self::NOT_CHECKED,
                'ok' => false,
                'value' => 'невозможно проверить',
                'detail' => 'Метка последней проверки worker имеет некорректный формат.',
                'icon' => 'ri-stack-line',
            ];
        }

        if (is_string($lastFailureValue) && $lastFailureValue !== '') {
            try {
                $lastFailure = Carbon::parse($lastFailureValue);
            } catch (\Throwable) {
                return [
                    'status' => self::NOT_CHECKED,
                    'ok' => false,
                    'value' => 'невозможно проверить',
                    'detail' => 'Метка неуспешной проверки worker имеет некорректный формат.',
                    'icon' => 'ri-stack-line',
                ];
            }

            if ($lastFailure->greaterThanOrEqualTo($lastSuccess)) {
                return self::failedQueueResult($lastFailureValue, $lastSuccess);
            }
        }

        $lastSuccessText = $lastSuccess->format('d.m.Y H:i:s');
        $isFresh = $lastSuccess->betweenIncluded(now()->subMinutes(self::QUEUE_HEALTH_MAX_AGE_MINUTES), now()->addMinute());
        $status = $isFresh && ($failed === null || $failed === 0) ? self::PASS : self::FAIL;
        $parts = ['Последняя успешная проверка worker: ' . $lastSuccessText . '.'];

        if (! $isFresh) {
            $parts[] = 'Проверка устарела (лимит ' . self::QUEUE_HEALTH_MAX_AGE_MINUTES . ' мин).';
        }
        if ($failed !== null && $failed > 0) {
            $parts[] = 'Необработанных failed_jobs: ' . $failed . '.';
        }
        if (! $databaseAvailable) {
            $parts[] = 'Количество jobs не проверялось: база данных недоступна.';
        } elseif ($jobs !== null) {
            $parts[] = 'В очереди задач: ' . $jobs . '.';
        }

        return [
            'status' => $status,
            'ok' => $status === self::PASS,
            'value' => ($isFresh ? 'worker подтверждён' : 'проверка устарела') . ' · ' . $lastSuccessText,
            'detail' => implode(' ', $parts),
            'last_success_at' => $lastSuccess->toIso8601String(),
            'icon' => 'ri-stack-line',
        ];
    }

    private static function failedQueueResult(string $lastFailureValue, ?Carbon $lastSuccess = null): array
    {
        try {
            $lastFailure = Carbon::parse($lastFailureValue);
        } catch (\Throwable) {
            return [
                'status' => self::NOT_CHECKED,
                'ok' => false,
                'value' => 'невозможно проверить',
                'detail' => 'Метка неуспешной проверки worker имеет некорректный формат.',
                'icon' => 'ri-stack-line',
            ];
        }

        $detail = 'Worker не обработал health-check, запущенный ' . $lastFailure->format('d.m.Y H:i:s') . '.';
        if ($lastSuccess) {
            $detail .= ' Последняя успешная проверка worker: ' . $lastSuccess->format('d.m.Y H:i:s') . '.';
        }

        return [
            'status' => self::FAIL,
            'ok' => false,
            'value' => 'worker не отвечает',
            'detail' => $detail,
            'last_success_at' => $lastSuccess?->toIso8601String(),
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

    private static function storage(?string $path = null, ?string $target = null): array
    {
        $path ??= public_path('storage');
        $target ??= storage_path('app/public');

        clearstatcache(true, $path);
        clearstatcache(true, $target);

        if (! file_exists($path) && ! is_link($path)) {
            return self::storageResult(self::FAIL, 'missing', 'На сервере выполните php artisan storage:link.');
        }

        $linkedTarget = @readlink($path);
        $isLink = is_link($path) || $linkedTarget !== false;
        $resolvedPath = realpath($path);
        $resolvedTarget = realpath($target);

        return self::classifyStorageLink($path, $isLink, $resolvedPath, $resolvedTarget);
    }

    private static function classifyStorageLink(
        string $path,
        bool $isLink,
        string|false $resolvedPath,
        string|false $resolvedTarget,
    ): array {
        if (! $isLink) {
            return self::storageResult(self::FAIL, 'incorrect target', $path . ' существует, но не является ссылкой или Windows junction.');
        }

        if ($resolvedPath === false || $resolvedTarget === false) {
            return self::storageResult(self::NOT_CHECKED, 'unable to verify', 'Не удалось разрешить фактический путь ссылки или ожидаемой директории.');
        }

        if (self::normalizePath($resolvedPath) !== self::normalizePath($resolvedTarget)) {
            return self::storageResult(self::FAIL, 'incorrect target', $path . ' -> ' . $resolvedPath . '; ожидается ' . $resolvedTarget . '.');
        }

        return self::storageResult(self::PASS, 'correct', $path . ' -> ' . $resolvedTarget . '.');
    }

    private static function storageResult(string $status, string $value, string $detail): array
    {
        return [
            'status' => $status,
            'ok' => $status === self::PASS,
            'value' => $value,
            'detail' => $detail,
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

    private static function recentErrors(?array $logPaths = null): array
    {
        $logPaths ??= self::laravelLogPaths();
        $existingPaths = array_values(array_filter($logPaths, 'is_file'));
        $count = 0;

        foreach ($existingPaths as $logPath) {
            if (! is_readable($logPath)) {
                continue;
            }

            $size = filesize($logPath) ?: 0;
            $handle = fopen($logPath, 'rb');

            if ($handle) {
                if ($size > self::MAX_LOG_BYTES_PER_FILE) {
                    fseek($handle, -self::MAX_LOG_BYTES_PER_FILE, SEEK_END);
                }

                $chunk = stream_get_contents($handle) ?: '';
                fclose($handle);

                $count = collect(preg_split('/\r\n|\r|\n/', $chunk))
                    ->filter(fn ($line) => self::isRecentErrorLine($line))
                    ->count() + $count;
            }
        }

        return [
            'ok' => $count === 0,
            'value' => $count . ' записей',
            'detail' => $existingPaths !== []
                ? 'Проверены последние ' . self::formatBytes(self::MAX_LOG_BYTES_PER_FILE) . ' каждого актуального Laravel-лога (' . count($existingPaths) . ' файлов) за 24 часа.'
                : 'Актуальные Laravel-логи пока не найдены.',
            'icon' => 'ri-bug-line',
        ];
    }

    private static function laravelLogPaths(): array
    {
        $channel = (string) config('logging.default', 'stack');
        $channels = $channel === 'stack'
            ? config('logging.channels.stack.channels', [])
            : [$channel];
        $channels = is_array($channels) ? $channels : [$channels];
        $paths = [];

        foreach ($channels as $name) {
            $driver = (string) config("logging.channels.{$name}.driver");
            $basePath = (string) config("logging.channels.{$name}.path", storage_path('logs/laravel.log'));

            if ($driver !== 'daily') {
                if ($driver === 'single') {
                    $paths[] = $basePath;
                }

                continue;
            }

            $extension = pathinfo($basePath, PATHINFO_EXTENSION);
            $base = $extension === '' ? $basePath : substr($basePath, 0, -(strlen($extension) + 1));
            $suffix = $extension === '' ? '' : '.' . $extension;
            $paths[] = $base . '-' . now()->format('Y-m-d') . $suffix;
            $paths[] = $base . '-' . now()->subDay()->format('Y-m-d') . $suffix;
        }

        return array_values(array_unique($paths ?: [storage_path('logs/laravel.log')]));
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
        try {
            if (! $table || ! Schema::hasTable($table)) {
                return null;
            }

            return DB::table($table)->count();
        } catch (\Throwable) {
            return null;
        }
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
        $normalized = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($normalized) : $normalized;
    }
}
