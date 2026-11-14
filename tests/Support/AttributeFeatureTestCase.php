<?php

namespace Tests\Support;

use Tests\TestCase;

/** These suites use existing schema + rollback, never the global truncating pilot. */
abstract class AttributeFeatureTestCase extends TestCase
{
    public static function assertNonCleaningMode(mixed $mode): void
    {
        if ($mode !== false && $mode !== '') {
            throw new \RuntimeException('Attribute Feature tests require WV_EXISTING_SCHEMA to be unset; automatic cleanup is forbidden.');
        }
    }

    public function createApplication()
    {
        // Before bootstrap, parent setUpTraits and ExistingSchemaMode::prepare().
        self::assertNonCleaningMode(getenv('WV_EXISTING_SCHEMA'));

        return parent::createApplication();
    }
}
