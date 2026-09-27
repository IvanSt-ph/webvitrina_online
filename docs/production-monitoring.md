# PROD-09 — Production health and operational monitoring

## Reassessment before changes

Repository inspection found the following (server setup was not inspected):

| Component | Before PROD-09 |
| --- | --- |
| Application | Laravel `/up` liveness; framework diagnostics could render exception text |
| Database | `ProductionHealth::database()` executes `select 1`, catches failures |
| Admin | `/admin/production-checklist` protected by auth and AdminMiddleware; checks run on page load |
| Queue | Manual `queue:health-check` waits for a token written by an actual job; persistent success/failure markers; 15-minute freshness |
| Scheduler | Checklist explicitly said not checked; no persistent heartbeat |
| Failed jobs | Count folded into queue result, unreadable count could be ignored |
| Backup | Scheduled daily `backup:run`, overlap protection; manual `backup:health-check` and admin checks |
| Backup validity | Age limit 30 hours, required DB/public/private archives, v2 manifest, SHA256; consistent DB snapshot already implemented in PROD-03 |
| Disk/storage | Basic 1 GiB threshold on application filesystem; storage-link target checked; disk API warnings not guarded |
| Logs | Size and bounded recent-error inspection; daily channel exists, default stack used single |
| Alerts | Marketplace DB notifications and optional preference-dependent email; no reliable operational transport |
| Server docs | Minute cron, Supervisor worker and manual queue/backup checks documented; deployment not proven |
| Local Windows | Task Scheduler runner and worker loop; hourly backup recovery only for development |

Search covered `Schedule::`, daily/hourly tasks, health/heartbeat, failed_jobs,
backup, `Log::`, LOG_CHANNEL and daily/stack/single configuration in application,
routes, config, tools, deploy and documentation. There was no general production
health CLI command or independently configured operational monitor in the repo.
The old audit was already partly resolved by real queue acknowledgements,
backup completeness/checksums and consistent DB snapshots, protected admin
diagnostics, and deployment instructions. Regular health detection and alert
delivery remained unconfirmed.

## Current checks

Run as the same OS user/environment as the application:

```bash
php artisan production:health-check
php artisan production:health-check --json
php artisan production:health --json
```

Exit codes: **0 healthy**, **1 warning**, **2 critical/unavailable**. A PHP boot
failure can produce a different non-zero code and no JSON; monitors must treat
non-zero, timeout, invalid/missing output and missed execution as failures.
JSON includes `status`, `checked_at`, and named `checks` with `status`, `ok`,
`value`, `detail`. It is private operational output, not an HTTP API.
One failed component does not prevent the remaining checks from running.
`production:health` is an alias of `production:health-check`.

- Application: successful CLI boot and maintenance mode off. HTTP is checked
  independently via `/up`; CLI alone cannot prove nginx/PHP-FPM/network health.
- Database: `select 1`.
- Scheduler: minute scheduled callback writes `production-health:scheduler-last-run`
  to the existing default cache, before daily tasks. Missing, malformed, more than
  5 minutes old, over 1 minute in the future, or unreadable means critical.
  Process-local cache stores are rejected. Use the existing database cache table;
  no migration is added. Cache clearing causes a failure until the next tick.
- Queue: scheduled `queue:health-check --timeout=15` every 5 minutes, background
  execution with a 5-minute overlap lock. Enqueuing alone never writes success.
  A short atomic cache lock serializes dispatch; before adding a probe, the command
  checks for an existing probe row in the configured database queue (including
  reserved/delayed jobs). A stopped worker therefore retains at most one newly
  pending probe, instead of accumulating one every five minutes. Existing legacy
  duplicates are not removed. This guard follows the actual queue row and cannot
  expire while a job remains pending. Queue probes support the project's database
  queue driver; other async drivers are explicitly rejected until an equivalent
  bounded probe is implemented. Process-local cache is rejected. CLI timeout is
  clamped to 1–60 seconds; scheduled timeout is 15 seconds, excluding backend I/O.
  Only job execution writes success; timeout/dispatch/cache error or job failure
  is detectable. Success expires after 15 minutes; newer failures fail immediately.
  A delayed job can confirm recovery when it finally executes. This probes the
  configured default connection/queue, not every possible queue or worker process.
  A command waiting on an earlier pending probe accepts a newly processed token,
  never a pre-existing success timestamp. Dispatch/cache/query failures produce
  non-zero exit codes rather than uncaught exceptions. Overlap protection is a
  lease; a backend I/O hang lasting beyond it also requires OS process monitoring.
- Failed jobs: separate count on the configured failed-job database/table;
  any record, disabled/unsupported provider or unreadable table is critical.
  No pruning, flushing, retries or record deletion is performed by health checks.
  Investigate and resolve records explicitly; historical failures remain visible.
- Backup: reuse `BackupHealth::latest()` with SHA256 enabled. Missing/stale,
  incomplete archives, invalid manifest or checksums fail. Temporary unpublished
  backups are ignored. Freshness uses existing published directory mtime and
  BACKUP_MAX_AGE_HOURS. No restore is performed. Hashing reads all archive bytes;
  choose monitor frequency/timeouts appropriate for actual backup sizes.
- Disk: query application, storage and backup paths separately, including separate
  filesystems; below 1 GiB is warning, below 256 MiB is critical. API failure,
  missing mount/path or exception is critical/unavailable, never an uncaught error.
- Writable: runtime storage directories, bootstrap/cache, backup directory and
  configured Laravel log locations; writes/removes only a unique probe file.
  Existing active logs must be writable. No existing logs are removed.
- Storage: existing public storage link must resolve to the expected public uploads.
- Logging: disabled/missing logging, single channel or unlimited daily retention
  fails; normal daily rotation passes. Log-size and recent ERROR+ observations are
  warnings. Recent-error scanning is bounded to the last 1 MiB of current/yesterday
  Laravel logs, so it is an indicator rather than exhaustive error accounting.

The protected admin checklist also displays scheduler, failed jobs and writable
checks. The public `/up` returns exactly `{"status":"ok"}` (200), or
`{"status":"unavailable"}` (503) if a DiagnosingHealth listener fails, even with
debug enabled. No paths, DB settings, traces, filenames or queue payloads are
returned by this controller. Failures before routing require APP_DEBUG=false and
the web server's normal production error handling. `/up` is liveness only and
is intentionally accessible during maintenance; the CLI detects maintenance.

## Server setup required

```env
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=database
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=14
LOG_LEVEL=warning
BACKUP_DIR=/var/backups/webvitrina
BACKUP_MAX_AGE_HOURS=30
HEALTH_SCHEDULER_MAX_AGE_MINUTES=5
HEALTH_DISK_WARNING_BYTES=1073741824
HEALTH_DISK_CRITICAL_BYTES=268435456
```

Existing `.env` is not edited by this change. Explicit LOG_STACK=single continues
to override the new default and must be changed on the server. Rebuild config
cache and restart workers after deployment. The scheduler, CLI, worker and HTTP
processes must share cache settings/prefix and clocks; on multiple hosts, use a
shared database/Redis cache and monitor each host's filesystem separately.
The repository's default and the inspected local `.env` both use CACHE_STORE=database;
PHPUnit normally uses array. PROD-09 tests explicitly use isolated database/file
stores and separate PHP writer/reader processes to verify shared heartbeat visibility.
The database cache and lock tables already exist. Production must keep cache
connection/table/prefix identical between all PHP processes; configuration drift
or cache eviction will correctly appear as missing heartbeat.

Create the private backup directory and runtime directories with application-user
write access. Install the Supervisor example (or equivalent systemd worker), and
install the existing minute scheduler cron:

```cron
* * * * * cd /var/www/webvitrina && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Do not rely on a task inside Laravel scheduler to detect that scheduler stopped.
Install an independent systemd timer/monitor agent. Example files are in
`deploy/webvitrina-health.service.example` and `.timer.example`. Copy without the
`.example` suffix into `/etc/systemd/system`, adjust paths/user/PHP binary and run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now webvitrina-health.timer
sudo systemctl start webvitrina-health.service
sudo journalctl -u webvitrina-health.service -n 30
```

The service records JSON and a failing service status for warning/critical.
**A systemd failure or journal entry alone is not an alert to a human.** Connect
the existing infrastructure monitoring agent to the service result/non-zero exit,
or configure an operator-owned OnFailure unit. Alert also when no completed run
is observed for 10 minutes (adjust for backup hash duration). An alternative cron
monitor must capture the health exit code and send it through an already working
transport; merely redirecting output to /dev/null is not monitoring. No SMTP,
Telegram or SMS transport is introduced in this application.

Configure external uptime monitoring of `https://your-domain/up` (expected 200 and
exact status ok), alert on timeout/non-200, and test actual delivery to the on-call
operator. This detects host/network/PHP-FPM outages that cannot self-report.
The independent health command covers DB/queue/scheduler/backup/storage failures
that `/up` intentionally does not reveal. Monitor database connection hangs with
an outer timeout; the supplied systemd unit has TimeoutStartSec=240.

After the first scheduled tick and queue probe, run the CLI and inspect all checks.
Perform a controlled worker-stop and scheduler-stop drill and confirm alert delivery
and recovery; do not run these drills against production without a maintenance plan.
Verify a real recent backup and perform restore drills separately.

## Rotation and limitations

Laravel's existing Monolog daily handler now defaults to dated laravel-YYYY-MM-DD.log
with 14-day retention. Twilio/registration already use daily with the same retention
setting. Retention cleanup occurs on logging activity, not via these tests or health
commands. Existing laravel.log and emergency fallback logs are not removed/rotated
retroactively: handle legacy/emergency logs with server logrotate and retain what
operations requires. Supervisor worker output has explicit 10 MB / 5-file rotation.
Bound journald retention on the server; application code does not manage it.

No migrations or new alert transport. No production cron/worker, alert receiver or
real backup freshness is claimed verified from the development workstation. Disk
checks do not measure inodes, quotas, remote object stores or database-server disks.
Writable probes run as the invoking OS user and cannot prove another user's rights.
Logging configuration/filesystem checks do not prove external log delivery. SHA256
validity is not a restore test. These are remaining operational responsibilities.

## PROD-09 files and verification

Changed/added for this item (pre-existing PROD-04–08 working-tree changes are separate):

- `.env.example`
- `app/Console/Commands/ProductionHealthCheck.php`
- `app/Console/Commands/QueueHealthCheck.php`
- `app/Http/Controllers/LivenessController.php`
- `app/Jobs/QueueHealthCheckJob.php`
- `app/Support/DiskSpace.php`
- `app/Support/ProductionHealth.php`
- `bootstrap/app.php`
- `config/health.php`
- `config/logging.php`
- `deploy/supervisor-webvitrina-worker.conf.example`
- `deploy/webvitrina-health.service.example`
- `deploy/webvitrina-health.timer.example`
- `docs/deployment.md`
- `docs/production-monitoring.md`
- `docs/release-runbook.md`
- `resources/views/admin/production-checklist.blade.php`
- `routes/console.php`
- `tests/Feature/OperationalHealthTest.php`
- `tests/Support/health-process.php`

No PROD-09 migration. The address-snapshot migration already in the working tree
belongs to PROD-06.

18 added tests cover aggregate healthy state/CLI, scheduler fresh/missing/stale/
invalid/nonpersistent states, real database/file cross-process heartbeat visibility,
queue missing/stale/failed/processed states, enqueue timeout, bounded pending jobs
and real worker recovery, dispatch locking/error handling, failed jobs zero/present/
unavailable, backup missing/fresh/stale/hash/missing archive, disk warning/critical/
unavailable, writable/unavailable directories, logging retention, DB failure,
JSON/exit codes and public liveness success/sanitized failure/admin authentication.
Disk capacity is faked; backup/storage/cache fixtures are isolated. DB tests and
child processes enforce the existing `webv3_testing` guard. Child processes exit
and are stopped in finally; their namespaced DB heartbeat is explicitly removed.

Focused plus queue/backup regressions:

```text
php artisan test --filter='OperationalHealthTest|ProductionHealthTest|PrivateBackupTest|BackupSnapshotTest|ReleaseExperienceTest'
81 passed, 739 assertions (170.27s)
```

This includes all 4 PROD-03 snapshot tests, all 3 private backup tests, and the
existing queue/backup command and admin-page regression tests.

Final verification on 2026-09-26:

- Full `php artisan test`: **438 passed, 3185 assertions**, 403.54 seconds.
- `php -l`: all **38** changed/untracked PHP files in the working tree pass
  (including earlier production-hardening changes).
- All **4** changed Blade templates compile and their generated PHP passes syntax
  checks; PROD-09 itself changes only `admin/production-checklist.blade.php`.
- `git diff --check`: clean; final PROD-09 diff reviewed.
- Post-suite guarded inspection of `webv3_testing`: **0 jobs, 0 failed_jobs,
  0 health cache rows, 0 health lock rows**. No operational/backup fixture directories
  or `.health-*` probe files remain. `.env` and `.env.testing` hashes unchanged.
- No server cron/systemd/Supervisor installation or production alert delivery is
  claimed by these local checks.
