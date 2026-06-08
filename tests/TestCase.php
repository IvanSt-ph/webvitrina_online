<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_contains($database, 'testing')) {
            throw new \RuntimeException(
                "Refusing to run tests on non-testing database [{$database}]. Clear config cache and set DB_DATABASE to a dedicated testing database."
            );
        }
    }
}
