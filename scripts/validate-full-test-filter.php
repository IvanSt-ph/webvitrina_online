<?php

declare(strict_types=1);

// No application bootstrap or database access. Empty means absent only in the
// entrypoint; an explicitly supplied filter must be a short ASCII identifier.
$filter = getenv('WV_FULL_TEST_FILTER');
if (! is_string($filter) || strlen($filter) > 200
    || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)?\z/D', $filter) !== 1) {
    fwrite(STDERR, "Invalid PHPUnit filter: use ClassName, test_method or ClassName::test_method.\n");
    exit(70);
}
