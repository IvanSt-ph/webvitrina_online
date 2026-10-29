<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;

final class ExistingSchemaCleaner
{
    use SafeDatabaseTruncation;

    public function __construct(protected Application $app) {}

    public function clean(): void
    {
        // Deliberately bypass DatabaseTruncation::truncateDatabaseTables(): that
        // framework entry point may migrate or seed. Preserve the migrations table.
        $this->truncateTablesForAllConnections();
    }
}
