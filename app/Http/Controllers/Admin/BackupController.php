<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BackupHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class BackupController extends Controller
{
    public function index(): View
    {
        $health = BackupHealth::latest();
        $current = $this->currentDatabaseSnapshot();
        $backupCounts = $health['manifest']['row_counts'] ?? [];
        $comparison = collect($current['row_counts'])
            ->map(fn ($count, $table) => [
                'table' => $table,
                'current' => $count,
                'backup' => array_key_exists($table, $backupCounts) ? (int) $backupCounts[$table] : null,
                'diff' => array_key_exists($table, $backupCounts) ? $count - (int) $backupCounts[$table] : null,
            ])
            ->values();

        return view('admin.backups.index', compact('health', 'current', 'comparison'));
    }

    public function run(): RedirectResponse
    {
        set_time_limit(300);

        $exitCode = Artisan::call('backup:run');

        if ($exitCode !== 0) {
            $message = trim(Artisan::output()) ?: 'Команда backup:run завершилась с ошибкой.';

            return back()->with('error', 'Backup не создан: ' . $message);
        }

        $health = BackupHealth::latest();

        if (! $health['ok']) {
            return back()->with('error', 'Backup создан, но проверка не прошла: ' . implode(' ', $health['issues']));
        }

        return back()->with('success', 'Backup создан и проверен: ' . $health['latest_name']);
    }

    private function currentDatabaseSnapshot(): array
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $database = $connection->getDatabaseName();
        $tables = collect($pdo->query(
            'select table_name from information_schema.tables where table_schema = ' . $pdo->quote($database) . ' and table_type = ' . $pdo->quote('BASE TABLE')
        )->fetchAll(\PDO::FETCH_COLUMN) ?: [])->map(fn ($table) => (string) $table)->values();
        $importantTables = [
            'users' => 'Пользователи',
            'shops' => 'Магазины',
            'products' => 'Товары',
            'categories' => 'Категории',
            'orders' => 'Заказы',
            'reviews' => 'Отзывы',
            'ad_campaigns' => 'Реклама',
            'conversations' => 'Чаты',
            'messages' => 'Сообщения',
        ];
        $rowCounts = [];

        foreach ($importantTables as $table => $label) {
            if ($tables->contains($table)) {
                $rowCounts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`')->fetchColumn();
            }
        }

        return [
            'database' => $database,
            'tables_total' => $tables->count(),
            'row_counts' => $rowCounts,
            'labels' => $importantTables,
        ];
    }
}
