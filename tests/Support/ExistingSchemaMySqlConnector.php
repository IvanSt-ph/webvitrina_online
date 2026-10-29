<?php

namespace Tests\Support;

use Illuminate\Database\Connectors\MySqlConnector;

/** Validate again at the last boundary BEFORE PDO opens a connection. */
final class ExistingSchemaMySqlConnector extends MySqlConnector
{
    public function __construct(private readonly string $environment) {}

    public function connect(array $config)
    {
        TestDatabaseSafety::assertConfiguration($this->environment, [
            'default' => 'mysql', 'connections' => ['mysql' => $config],
        ]);

        $pdo = parent::connect($config);
        ExistingSchemaSessions::register($pdo);

        return $pdo;
    }
}
