# PROD-10 — Backup/Restore Atomicity & Safe Restore

## Confirmed failures

Before this change, the InnoDB dump committed before public and private archives.
A regression reproduction replaced a referenced file at the first archive call:
the SQL still referenced `old.txt`, both SHA256/health checks passed, and the archive
did not contain that file (1 passing reproduction, 5 assertions before the fix).
Checksums establish byte integrity, not DB/files consistency.

Restore previously ignored rollback `rename` results and unconditionally deleted
staging in `finally`, potentially deleting the sole previous-storage copy.
The old deployment drill created a test directory but invoked `--force` against
the current application's storage; a separate DB did not isolate files.

## Consistency boundary

The current local-filesystem architecture uses a stable `flock` inode at
`storage/framework/backup-writes.lock`. All PHP processes sharing uploads must
share this inode and working advisory-lock semantics. Do not delete/rotate this
file, place it in replaceable upload roots, or deploy separate per-release lock
files. There is no expiring lease, DB migration, cache row or maintenance flag.
Deploy the code to all HTTP/CLI processes and restart existing queue workers before
relying on this boundary. Independent uploads volumes/hosts with separate locks
are not supported by this model; such a deployment requires shared coordination
before claiming an application-consistent backup. NFS locking is not assumed.

T0: backup acquires the exclusive barrier, waiting up to 60 seconds for active
protected mutations (including commit/rollback cleanup) to finish. New mutations
wait outside their operation; after 60 seconds they fail without beginning it.
HTTP mutation timeouts return 503 with `Retry-After: 60`; controller code does not
run. CLI backup/restore commands report the barrier error and a failure exit code.

T1: open a fresh nonpersistent MySQL connection, set REPEATABLE READ, begin READ
ONLY WITH CONSISTENT SNAPSHOT, take metadata locks and verify all tables are InnoDB.

T2: export tables and row counts from that snapshot; commit and close the dump
connection; gzip the SQL. The exclusive barrier remains held.

T3: archive and gzip public storage.

T4: archive and gzip private/chat-images.

T5: write the unchanged v2 manifest and SHA256SUMS with checked writes/hashes.

T6: publish by renaming the owned `.tmp` directory; apply existing retention.

T7: release the barrier in `finally`; pending writers may proceed. The same barrier
is released on PHP exceptions/errors. OS process termination closes its handle;
an abrupt kill can leave an unpublished `.tmp` directory, which health ignores.
Such an artifact requires operator inspection/removal before retrying that exact
destination. No cleanup ever removes a pre-existing colliding destination.

HTTP mutation requests hold shared protection over the complete controller call.
This covers product/category/shop/avatar/review/banner/chat uploads and deletes,
model observers, account cleanup and synchronous after-commit callbacks. The
admin backup action takes the exclusive barrier itself. GET/HEAD browsing remains
available; current GET handlers do not mutate backed-up files or their paths.
Health writable probes acquire shared protection individually. ProductService
transactions also protect direct service calls and scheduled purge operations,
retaining the lock through enclosing transactions and their cleanup callbacks.
Each queue job is protected from JobProcessing through JobProcessed or exception;
idle workers and scheduler heartbeats do not hold the barrier. Ordinary unrelated
DB writes may continue: PROD-03 snapshot isolation still protects the SQL dump.

Direct `User::delete()` calls use the same transaction barrier, including an
enclosing transaction and account-deletion after-commit public/private cleanup.
The barrier remains held until both archives are complete. At that point the
captured DB/files state is immutable; holding it through manifest, checksums,
publication and retention is conservative and keeps one clear failure boundary.
No duration optimization is applied here. Shared writers can still enter while an
exclusive acquisition is waiting; the critical window starts only after it obtains
the lock. A sustained stream of writers can therefore cause a bounded timeout.

### Reviewed mutation inventory

| Mutation | Existing entry points | Protection |
| --- | --- | --- |
| Product main image, gallery, derivatives, replacement/deletion | Admin ProductController; Seller ProductManageController; ProductService; ProductImageOperation; ImageService | HTTP shared barrier plus ProductService transaction barrier for direct calls; commit/rollback compensation completes inside it |
| Old-product purge | PurgeOldProducts; scheduled products:purge-old; ProductService::purge | Per-product transaction barrier through deferred deletion |
| Avatar and shop banner | ProfileController; Admin UserController; ImageService | Mutating HTTP request; account deletion also has model-level transaction protection |
| Category icon, original, thumbnail, admin image replacement/deletion | CategoryController; Admin CategoryController | Mutating HTTP request |
| Review upload/replacement and deletion hooks | ReviewController; Admin ReviewController; Review::deleting; ImageService | Mutating HTTP request; account deletion collects files for protected after-commit cleanup |
| Chat attachment upload/deletion | ChatController; Admin ChatController; ChatImageService; MessageObserver; Conversation deletion cascade | Mutating HTTP request or queue job; account deletion has transaction protection |
| Admin banner upload, crop, replacement, deletion | Admin BannerController | Mutating HTTP request |
| Account deletion, public and private cleanup | ProfileController; Admin UserController; User::delete; AccountDeletionService | Model transaction barrier, retained through outer commit/rollback and guarded cleanup callbacks |
| Writable health probe | ProductionHealth::writable; production:health-check; admin checklist | Explicit shared barrier around unique probe creation/removal |
| Restore activation and rollback | RestoreBackupFiles; BackupStorageService::restore | Exclusive barrier |
| Queue execution | All current jobs, including sync/database workers | JobProcessing acquire; JobProcessed/JobExceptionOccurred release |

Repository-wide searches covered Storage writes/deletes, UploadedFile stores,
native filesystem mutations, model hooks, afterCommit, commands, jobs, routes and
seeders. Cache invalidation removes framework cache files, not upload roots.
Current other Artisan commands/jobs do not modify upload files or their references.
Low-level ImageService/ChatImageService/model hooks are not universal transaction
wrappers: a future standalone caller must protect its entire DB-plus-files operation,
not merely the individual filesystem call. Tinker, raw model/SQL updates, seeders,
shell scripts and external writers are outside the supported operational boundary.

New file-mutating GET handlers, console commands, scripts, deferred callbacks or
external file writers must join this barrier around **both** DB references and file
mutations. Raw SQL, shell file manipulation, deployments and schema changes are
operator-controlled activities outside this cooperative boundary. Do not run
them during backup. This is not a filesystem snapshot or an OS-wide write ban.

Backup never invokes `down` or `up`. Existing maintenance state and payload are
preserved on success and failure. DB/public/private/manifest/hash failures do not
publish; owned staging is cleaned when possible, cleanup errors identify its path.
Retention failure after publication reports failure but keeps the published copy.

## Isolated restore drill

Use `backup:restore-files /absolute/backup --drill=/absolute/empty-private-directory`.
The destination must already exist, be empty and be controlled by the operator.
The command chooses fixed distinct children `public` and `private/chat-images`;
arbitrary archive extraction paths and separate overlapping destination options
are not exposed. Live roots are never defaults in drill mode.

Validation rejects relative/empty paths, traversal, filesystem root, project root,
live public/private roots and their ancestors/descendants, backup-source overlap,
symlink/junction aliases and nonempty destinations. Canonical existing ancestors
resolve protected paths that do not yet exist. Use a private parent directory:
concurrent privileged filesystem renames are outside this tool's trust boundary.
`--drill` and `--force` are mutually exclusive; no mode/confirmation is rejected.

Restore SQL separately with credentials limited to an isolated test database.
Run any application-level drill in a separate checkout with isolated storage,
cache/session/queue and disabled outbound integrations. File isolation alone
does not isolate SQL imports or other services.

## Production destructive restore and recovery

`backup:restore-files /absolute/backup --force` explicitly replaces configured
live public and private/chat-images. Coordinate the DB restore with stopped
writers/maintenance for the entire DB-plus-files operation. The command's barrier
protects file installation only; it cannot wrap a separately invoked SQL import.

Both archives are health/checksum validated and extracted before active files move.
Previous roots move into `.backup-restore-UUID/.previous-public` and
`.previous-chat-images`. After both replacements and private permissions succeed,
installation commits; only then may cleanup remove previous copies.

On activation/permissions failure, each installed new tree is moved back to staging,
then each saved old tree is renamed home. Every rename is checked independently.
If any rollback fails, no staging cleanup runs. The error retains the original
exception and lists the recovery directory and failing roots. Stop writers, inspect
both live and saved trees, restore the named previous copies, verify contents and
DB correspondence, then remove artifacts manually. Never blindly delete staging.

Cleanup failure after installation reports that new storage is active and gives
the residual staging path; it never tries rollback after partial old-copy cleanup.
Cleanup failure after a confirmed rollback reports that old storage is active.
Process/host death mid-activation is not a two-directory atomic filesystem operation:
recovery may require the preserved staging copies and operator reconciliation.

Manifest/archive format remains v2. Existing complete v2 backups remain restorable;
their original DB/files consistency is not retroactively guaranteed. Incomplete
legacy backups remain rejected. No migrations or Blade changes in PROD-10.
