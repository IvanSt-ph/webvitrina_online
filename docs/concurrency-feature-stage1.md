# Concurrent Feature preparation — stage 1

Previous lifecycle group: user-reported PASS, 355 distinct tests / 910 assertions.
Focused and repeated runs are not counted again. No completed suite was rerun here.

At resumption, none of the five concurrency classes or their workers had pending edits.
Earlier unrelated working-tree changes were preserved. This preparation did not execute tests,
workers, database connections, SQL, migrations, build or dependency audits.

## Scope and review

| Class | Cases | Prior exclusion / changes |
| --- | ---: | --- |
| CheckoutIdempotencyConcurrencyTest | 8 provider cases | Factory cleanup probe bypassed ConnectionEstablished and opened PDO outside finally. Uses guarded connectUsing now; validates and disconnects even on failure. Verifies worker sessions gone before probing checkout locks. Keeps crash-before/after-commit, uncertain commit, rollback, same/different token and insufficient-stock scenarios. |
| OrderStockConcurrencyTest | 2 | Worker and shared lock-probe paths needed review. Uses exhaustive process cleanup and the corrected shared probe. Keeps simultaneous legacy cancellation and injected parent-failure scenario, stock restored exactly once. This intentionally tests legacy orders (workflow_version null), where Order::setStatus remains the allowed API. |
| PickupOrderWorkflowConcurrencyTest | 4 | Parent locks, workers, probe and fixture transitions needed review. Initial pending fixture is saved once; processing/ready transitions use OrderPickupWorkflow. Removed SQL updates of persisted lifecycle state. Counts include two real preparation events/notices; still requires exactly three concurrent completion events and zero reminder notices. Parent rollback failures cannot skip worker cleanup. |
| PhoneUniquenessConcurrencyTest | 1 | ATTEMPT alone did not establish actual contention, cleanup lacked session/named-lock verification. Second worker now proves GET_LOCK(...,0) conflict, waits for continue, then calls the unmodified PhoneAssignmentService. Parent verifies sessions/row locks released and IS_USED_LOCK is null. Worker disconnects in finally. |
| Auth/CredentialConcurrencyTest | 9 provider cases | Independent credential workers and probe needed review. Uses exhaustive cleanup and guarded shared probe; retains real user-row NOWAIT conflict, token consumption, stale snapshots, rollback and HTTP session/device revocation assertions. |

Expected total: 24 Feature cases, not a test result. Only these five classes were added to
ExistingSchemaMode; corresponding negative Unit cases moved to positive coverage with
subclass rejection retained. Other exclusions are unchanged.

## Isolation and cleanup

Every worker already calls TestWorkerBootstrap before fixture queries. It validates supplied
configuration before application bootstrap, inherits and verifies the parent's isolated file
paths, installs ExistingSchemaMode after configuration loading, restricts configured connections
to mysql, then verifies actual write-PDO DATABASE()/CURRENT_USER(). Allowed target remains
webv3_testing / webvitrina_test@localhost at 127.127.126.9:3306. No environment file is modified.
The existing guarded connector also checks metadata before opening PDO.

Workers inherit isolated compiled views, storage, logs and backup barrier path. Cache, mail,
session and queue defaults remain array/array/array/sync. Credential parent intentionally uses
database sessions in the same guarded test DB; worker sessions remain array. No real backup
or external queue worker is started. Credentials are passed via stdin, not command arguments.

WorkerProcessCleanup attempts every stdin close and every owned Process::stop(0), even after
an earlier cleanup failure, and refuses successful completion if any process survives.
Only Process instances created by the test are targeted. No shell-wide or port-based killing.
This preserves Symfony's Windows process termination handling.

After worker exit, ConcurrencyLockProbe verifies fixture rows with NOWAIT and confirms recorded
connection IDs have disappeared from SHOW PROCESSLIST. Its connection now goes through the
DatabaseManager SQL-guard event, opens PDO within finally coverage, and is purged afterwards.
Raw PDO lock probes retain actual-PDO checks and now also invoke the existing SQL guard.
The phone test additionally verifies release of the named lock.

On intentional crashes, process termination releases PDO sessions and transaction/OS resources;
lock/session probes confirm release rather than assuming a sleep was enough. Before the next
fixture TRUNCATE, existing ownership checks reject unknown/active sessions. Do not rerun after
cleanup failure until remaining processes are inspected. Real COMMIT/rollback and independent
PHP sessions remain; none were replaced by mocks or sequential business operations.

## Contracts and remaining risks

Reviewed CheckoutController's product locks/stock check and idempotent checkout path,
Order's legacy status restrictions, OrderPickupWorkflow participant-before-order locking and
completion/payment requirements, AccountDeletionService's terminal-order requirement,
PhoneAssignmentService's normalized named-lock protocol and PasswordSecurityService's user
row locking. No production changes were made or runtime compatibility asserted.

Windows scheduling, MySQL disconnect latency, deadlocks and timeouts remain runtime risks.
Existing synchronization windows (8 seconds) and process limits (20–40 seconds) are retained;
cleanup probes poll for up to 5 seconds. Failures must be diagnosed, not hidden by retries,
relaxed assertions or unreviewed timeout increases. Shared ConcurrencyLockProbe also serves
other tests, but no other Feature classes were prepared or admitted.

## User-run commands

Use an existing safe .env.testing and a fresh PowerShell session without profile overrides.
Existing-schema explicitly TRUNCATEs test fixture tables; do not preserve data in this DB.
The runner checks actual connection identity before each file. Run sequentially, stop at error.

```powershell
Set-Location C:\webvitrina_online
php vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Unit/ExistingSchemaModeTest.php
if ($LASTEXITCODE -ne 0) { throw 'Stop: ExistingSchemaModeTest' }
$files = @(
    'tests/Feature/CheckoutIdempotencyConcurrencyTest.php',
    'tests/Feature/OrderStockConcurrencyTest.php',
    'tests/Feature/PickupOrderWorkflowConcurrencyTest.php',
    'tests/Feature/PhoneUniquenessConcurrencyTest.php',
    'tests/Feature/Auth/CredentialConcurrencyTest.php'
)
foreach ($file in $files) {
    php scripts/run-safe-feature-tests.php --mode=existing-schema "--file=$file" --ack-cleanup=webv3_testing --run
    if ($LASTEXITCODE -ne 0) { throw "Stop: $file" }
}
```

Prepared for user verification only; no PASS claimed.
