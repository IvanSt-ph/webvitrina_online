#!/bin/sh
set -eu

if [ "${WV_DISPOSABLE_FULL_TEST:-}" != "1" ]; then
    echo "Refusing to start outside the disposable full-test profile." >&2
    exit 70
fi

if [ ! -r "${DB_PASSWORD_FILE:-}" ]; then
    echo "The disposable database password secret is unavailable." >&2
    exit 70
fi

export DB_PASSWORD
DB_PASSWORD="$(cat "$DB_PASSWORD_FILE")"
unset DB_PASSWORD_FILE

php /app/scripts/full-test-preflight.php

case "${WV_FULL_TEST_EXECUTE:-0}" in
    0)
        echo "Isolation preflight passed. PHPUnit was not requested."
        exit 0
        ;;
    1)
        php -r 'fwrite(STDOUT, "Disposable PHP CLI memory_limit=" . ini_get("memory_limit") . PHP_EOL);'
        # Fixed bootstrap/configuration; filter is a validated single argv value.
        # The PHPUnit bootstrap repeats preflight after XML environment handling.
        set --
        if [ -n "${WV_FULL_TEST_FILTER:-}" ]; then
            php /app/scripts/validate-full-test-filter.php
            set -- --filter "$WV_FULL_TEST_FILTER"
            printf 'PHPUnit filter: %s\n' "$WV_FULL_TEST_FILTER"
        fi
        exec php /app/vendor/phpunit/phpunit/phpunit \
            --configuration /app/phpunit.full-test.xml --do-not-cache-result --debug --colors=never "$@"
        ;;
    *)
        echo "Invalid disposable test execution mode." >&2
        exit 70
        ;;
esac
