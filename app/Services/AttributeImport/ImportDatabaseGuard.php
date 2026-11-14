<?php

namespace App\Services\AttributeImport;

use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\DatabaseManager;

final class ImportDatabaseGuard
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly Repository $config,
    ) {}

    public function assertAllowed(): void
    {
        $allowed = $this->config->get('attribute_import.allowed_database');
        if (! is_string($allowed) || $allowed === '' || strtolower($allowed) === 'webv3' || trim($allowed) !== $allowed) {
            throw new ImportException('Импорт заблокирован: не настроена разрешённая база данных импорта.');
        }

        try {
            // Query builder and both writing/locking models must use checked connections.
            $names = [null, (new Attribute)->getConnectionName(), (new Category)->getConnectionName()];
            foreach (array_unique(array_filter([
                $this->config->get('cache.default'),
                $this->config->get('cache.limiter') ?: $this->config->get('cache.default'),
                in_array($this->config->get('session.driver'), ['apc', 'memcached', 'redis', 'dynamodb'], true)
                    ? ($this->config->get('session.store') ?: $this->config->get('session.driver')) : null,
            ])) as $store) {
                $settings = $this->config->get('cache.stores.'.$store, []);
                if (($settings['driver'] ?? null) === 'database') {
                    $names[] = $settings['connection'] ?? null;
                    $names[] = $settings['lock_connection'] ?? $settings['connection'] ?? null;
                }
            }
            if ($this->config->get('session.driver') === 'database') {
                $names[] = $this->config->get('session.connection');
            }

            $checked = [];
            foreach ($names as $name) {
                $connection = $this->database->connection($name);
                $key = spl_object_id($connection);
                if (isset($checked[$key])) {
                    continue;
                }
                $checked[$key] = true;
                if ($connection->getDriverName() !== 'mysql') {
                    throw new ImportException('Импорт заблокирован: требуется проверяемое подключение MySQL.');
                }
                // false selects the WRITE PDO, never a read replica or configured DB name.
                $row = $connection->selectOne('SELECT DATABASE() AS database_name', [], false);
                $actual = $row->database_name ?? null;
                if (! is_string($actual) || $actual === '') {
                    throw new ImportException('Импорт заблокирован: не удалось определить фактическую базу данных.');
                }
                if ($actual !== $allowed) {
                    throw new ImportException('Импорт заблокирован: фактическая база данных не совпадает с разрешённой базой импорта. Проверьте конфигурацию сервера.');
                }
            }
        } catch (ImportException $exception) {
            throw $exception;
        } catch (\Throwable) {
            // Do not expose SQL, credentials or connection errors to the browser.
            throw new ImportException('Импорт заблокирован: не удалось безопасно проверить подключение к базе данных.');
        }
    }
}
