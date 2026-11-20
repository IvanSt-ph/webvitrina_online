# Lifecycle Feature preparation

Previous group, as reported by the user: 245 distinct tests / 1688 assertions passed.
This includes only one of the two successful SecurityRegressionTest runs. No rerun was performed.

This preparation used source inspection only. No tests, database connections, SQL, build,
dependency audit, migrations or seeders were executed. Production code is unchanged.

## Prepared existing-schema targets

| Class | Cases including providers | Previous barrier | Changes and retained contract |
| --- | ---: | --- | --- |
| AdminAvatarLifecycleTest | 24 | Real commits, public image deletion, uncertain-commit cleanup and backup barrier handle required review | Assert production manager and zero outer transactions; verify original write-PDO before manual rollback; guaranteed disconnect after callback failure; restore mocked public disk in finally; include mock setup in barrier handle cleanup scope. Retain all failure, commit, savepoint and old/new image assertions. |
| ChatImageLifecycleTest | 31 | Real commits and private attachment deletion/compensation required review | Assert production manager and zero outer transactions; verify original write-PDO before uncertain-commit rollback. Retain public/private fake disks, event dispatcher restoration, privacy, rollback and deletion assertions. |
| OrderIdentitySnapshotTest | 11 | Historical image copying, backfill and real rollback required review | Restore mocked public disk in finally. Preserve immutable snapshots, historical images after product deletion, stock compensation and explicit real-transaction scenario. |

Expected new runnable group: 66 cases. These counts are static, not execution results.
No additional connections or child processes are introduced by these classes. Existing-schema
isolates storage and fake disks, compiled views and backup barrier locks. Array mail/cache/session
and sync queue remain selected by the safe launcher. No real backup is created or restored.
The order backfill command operates on guarded test fixtures, not migrations or an Excel import.
ExistingSchemaMode adds only these three classes. Guards and SQL rules are unchanged.

Residual risks: runtime image extension support, filesystem/Windows lock behavior, callback
assertions and schema compatibility require user execution. Do not weaken assertions after failure.

## ReleaseExperienceTest remains excluded (49 cases)

The dashboard/checklist case changes application storage/public paths, creates a symlink or
Windows junction and later recursively deletes its temporary root. Other cases create backup
health fixtures. The current scope is insufficient for its full health checks:

- ProductionHealth::writableUnderBarrier uses base_path('bootstrap/cache'), independent of the
  isolated Laravel cache filenames, and config('backup.path'). It creates temporary write probes.
- ExistingSchemaFileIsolation redirects backup.lock_path, but does not redirect backup.path.
- The test's restoration callback does not restore backup.lock_path, and recursive cleanup follows
  link removal without enforcing successful removal first.

This is a test-isolation gap, not evidence of a production vulnerability. Do not launch this class
yet. It needs a focused temporary application base/public/storage/backup sandbox that preserves
real checklist rendering, verifies link targets, removes links before recursive cleanup and
restores every changed binding/configuration in finally. No assertions or health behavior were
stubbed to admit it. Windows symlink/junction capability must be explicitly handled.

## Unit expectations

TransactionCallbackContractTest moved from the expected-denied dataset to the expected-allowed
dataset; its already-approved allowlist entry is unchanged. The three newly reviewed classes
also moved to expected-allowed. ReleaseExperienceTest remains an explicit negative case.
Previous four priority classes remain covered. Rejected subclasses and all other excluded
classes remain checked. Static comparison found no intersection of literal allowed/denied classes.

## User commands

Use a fresh PowerShell session without a profile. The existing .env.testing must identify
webv3_testing / webvitrina_test@localhost at 127.127.126.9:3306. The launcher verifies actual
DATABASE()/CURRENT_USER() before execution. Existing-schema TRUNCATEs test fixture tables;
do not run if that database contains data to preserve. Stop on the first error.

```powershell
Set-Location C:\webvitrina_online
php vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Unit/ExistingSchemaModeTest.php
if ($LASTEXITCODE -ne 0) { throw 'Stop: ExistingSchemaModeTest' }
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/AdminAvatarLifecycleTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'Stop: AdminAvatarLifecycleTest' }
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/ChatImageLifecycleTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'Stop: ChatImageLifecycleTest' }
php scripts/run-safe-feature-tests.php --mode=existing-schema --file=tests/Feature/OrderIdentitySnapshotTest.php --ack-cleanup=webv3_testing --run
if ($LASTEXITCODE -ne 0) { throw 'Stop: OrderIdentitySnapshotTest' }
```

No execution command for ReleaseExperienceTest is authorized by this preparation; the launcher
continues to reject it. No PASS is claimed for any unexecuted test.
