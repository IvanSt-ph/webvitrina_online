# Priority Feature test preparation

Prepared by static inspection only. No tests or database connections were executed.
Production code, dependencies, environment files and database guards are unchanged.

## Reviewed targets

| Class | Previous exclusion | Preparation | Expected cases |
| --- | --- | --- | ---: |
| InputRenderingSecurityTest | Node execution and inline Blade needed filesystem review | Retained real rendering, DOM/plain-mail assertions and Node VM payload evaluation; isolated child working directory, timeout and finally-stop | 37 (5 methods with providers) |
| Auth/RegistrationTest | Local CSP test changed the database application's environment | Registration HTTP requests remain in testing; real SecurityHeaders middleware runs on the response in a separate application container, restored in finally | 6 |
| CheckoutNotificationAtomicityTest | Factory probe bypassed ConnectionEstablished SQL protection; PDO opened outside finally | DatabaseManager connectUsing registers SQL guard; metadata and actual write-PDO checked inside disconnect finally; real root commits retained | 3 |
| SecurityRegressionTest | Production environment switch, independent factory probe and checkout workers needed review | Isolated CSP container; guarded probe; explicit BOOTED/CLOSED worker assertions, existing conflict/NOWAIT checks retained | 196 |

Total expected: 242 Feature cases. Counts are derived from source, not execution.
ExistingSchemaMode admits only these four additional classes. Other exclusions stay in place.

## Safety and scope

All four use existing-schema. This mode **TRUNCATEs fixture tables in webv3_testing** before cases;
it is not appropriate for a database containing data to preserve. It does not migrate or seed.
Even InputRenderingSecurityTest uses this cleanup-capable mode because it boots the full test
application; no new partially protected launch mode was introduced.

The launcher verifies metadata and actual DATABASE()/CURRENT_USER() before PHPUnit.
Required target: webv3_testing, webvitrina_test@localhost, 127.127.126.9:3306.
It reads the existing .env.testing, never falls back to .env, and refuses conflicting overrides.
TestDatabaseSafety, guarded connector, SQL interception and filesystem isolation remain active.

Registration uses ordinary RefreshDatabase transactions after protected fixture cleanup.
CheckoutTestCase deliberately has no outer rollback wrapper: notification visibility and
queued database jobs are checked through a separate PDO before and after real COMMIT.
On failure its existing teardown rolls back outstanding transactions.
Probe connections disconnect in finally, including connection verification failures.

SecurityRegressionTest ordinarily uses RefreshDatabase; its overlapping checkout case explicitly
ends the outer transaction, retains the production transaction manager and invokes the existing
TestWorkerBootstrap workers. Workers inherit the isolated paths and guarded connection settings.
The parent closes stdin/stops processes in finally; its independent NOWAIT probe checks release
of row locks and rolls back/disconnects in nested finally blocks. There are no named MySQL locks
introduced by these changes. Worker exit also releases process-owned handles/connections.
Mail/cache/session use array drivers; queues are sync except the atomicity test's explicit
database queue. Image tests use fake disks under the existing isolated storage tree.

CSP tests still exercise real SecurityHeaders, not fabricated header values. The HTTP request
itself stays in testing, followed by isolated local/production header evaluation; these cases
do not claim to validate an entire production boot. The original container is restored even
if writing headers throws. A new Unit class prepares three cases for that contract; the existing
allowlist Unit class adds four accepted-class/rejected-subclass cases. Neither was run.

## User-run commands

Run from a new PowerShell session without a profile (`pwsh -NoProfile`), with PHP and Node on PATH.
Do not edit .env or bypass a failed preflight. Execute sequentially and stop at the first failure:

```powershell
Set-Location C:\webvitrina_online
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/InputRenderingSecurityTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'InputRenderingSecurityTest failed; stop.' }
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/Auth/RegistrationTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'RegistrationTest failed; stop.' }
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/CheckoutNotificationAtomicityTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'CheckoutNotificationAtomicityTest failed; stop.' }
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/SecurityRegressionTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'SecurityRegressionTest failed; stop.' }
```

## Remaining uncertainty

Ready for guarded user execution, not a PASS claim. Node availability, GD/image support,
schema currency, concurrency timing and legacy UI expectations must be confirmed by execution.
No production defect is demonstrated by these static infrastructure changes.
Do not weaken assertions if the runs reveal stale expectations or a production regression.
