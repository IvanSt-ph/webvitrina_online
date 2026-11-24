<?php

namespace Tests\Support;

use Illuminate\Database\Connectors\MySqlConnector;

final class DisposableMySqlConnector extends MySqlConnector
{
    public function connect(array $config)
    {
        DisposableTestEnvironment::assertConfiguration('testing', [
            'default' => 'mysql', 'connections' => ['mysql' => $config],
        ]);
        $pdo = parent::connect($config);
        DisposableTestEnvironment::assertPdo($pdo);

        return $pdo;
    }
}
