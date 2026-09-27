# PROD-07: transaction-safe product images

## Audit and flows

Admin `ProductController` and seller `ProductManageController` both call
`ProductService` for create, update, soft delete and the separate gallery DELETE
endpoint. Their controllers do not wrap these calls in an outer transaction.
`ProductCrudRepository` performs model writes and cache invalidation. The Product
model uses SoftDeletes; its hooks manage slugs and caches, not files.

Previously replacement, gallery removal and soft deletion called `ImageService::delete`
before the surrounding transaction committed. The separate gallery endpoint had
no transaction. A later DB/attribute/gallery error could restore references to
already deleted files. Successful uploads also survived subsequent DB rollback.
ImageService already compensated a partially written medium/thumb pair and a
partially uploaded gallery, but did not cover failures after those methods returned.

`products:purge-old` previously bulk-force-deleted records older than 90 days without
file cleanup. It now uses the same file lifecycle, with per-product transactions,
row locks and an eligibility recheck. Its retention period is unchanged.

PROD-04 account deletion is a separate existing flow: it retains ordered products,
force-deletes unreferenced products and schedules collected files after commit.
That service and its business behavior were not changed by PROD-07. No other
product-image Storage/File/unlink/directory-deletion flow was found. Category,
banner, review, avatar, chat and backup lifecycles remain outside this task.

## Transaction and filesystem behavior

`ProductImageOperation` belongs to one service operation. New images are written
before commit under fresh UUID names; every completed upload is recorded before
the next image or database write. The gallery loop uses this same operation, so
a failure in any batch member or later attribute write compensates all new files,
including a newly uploaded main image. Partial medium/thumb write failures are
handled inside ImageService itself.

Existing files are scheduled with the actual connection's `afterCommit`. No old
file is removed on rollback. Soft delete retains the established business rule:
hide the product and remove its images on success; it does not promise image
restoration if an administrator later restores the soft-deleted row.

Deletion attempts both stored image and thumbnail, even if one fails. Product
cleanup requests checked deletion, catching exceptions and false storage results.
A post-commit failure is logged with path, phase and error for manual retry; it
does not fail an already committed product update. Rollback cleanup errors likewise
do not mask the original operation error or prevent cleanup of subsequent images.
There is no new queue, schema or migration.

ImageService currently creates only `medium/<uuid>.webp` and `thumb/<uuid>.webp`.
It does not retain an uploaded original. Historical original paths (for example
`products/photo.jpg`) and their existing `thumb/photo.webp` convention receive the
same commit/rollback treatment. No speculative third derivative is created or
deleted. Protected placeholder/default basenames are checked before deletion of
either file. Before deleting an old product image, active products are checked
for main-image or gallery references, preserving existing shared files.

## Nested transactions and concurrency

The installed framework is Laravel 12.69.2. Its production transaction manager
runs commit callbacks at level zero. However, rollback of an outer transaction
discards already committed child records without invoking their rollback callbacks.
Using only the child's `afterRollBack` would therefore leak new images.

The operation registers an idempotent compensation callback on every currently
pending transaction record for the same connection, including all ancestors.
Thus local exceptions, manual outer rollback, rollback to an intermediate savepoint,
and later rollback after a successful service call all clean new files. A genuine
commit marks the operation finished. This uses the installed framework's public
transaction-record APIs and must be reverified when Laravel is upgraded.

Update, gallery deletion, soft delete and purge re-read the product with
`lockForUpdate` inside their transaction. Incoming stale model image/gallery values
are not used for mutation or cleanup. Locks last through any outer transaction.
Uploads use unique names, and service payload filtering excludes client-supplied
image/gallery paths, so normal application flows cannot reattach an old path
between the reference check and deletion. Main/gallery sharing on one product and
existing cross-product sharing are preserved until the last active reference goes.

## Verification

`ProductImageLifecycleTest` adds 19 tests using guarded MySQL `webv3_testing`, real
commits and the production transaction manager. Standard `DatabaseTruncation`
isolates fixtures without RefreshDatabase's transaction wrapper.
It covers replacement, outer/intermediate rollback, late repository/attribute errors,
create rollback, batch and partial derivative failures, gallery deletion, soft delete,
purge, legacy originals, placeholders, shared references, stale models, post-commit
filesystem failure, and real admin/seller create/update/gallery/delete endpoints.
Two PHP workers additionally test actual MySQL row-lock contention during overlapping
replacements and gallery appends, checking final file/reference agreement.

- Focused `ProductImageLifecycleTest|AdminProductValidationTest`: 23 passed,
  198 assertions, 43.69 seconds.
- `php -l`: all six PHP files added/changed for PROD-07 passed.
- `git diff --check`: passed.
- Full `php artisan test --compact`: 403 passed, 2946 assertions, 391.55 seconds,
  including existing PROD-01/04/05/06 regressions.
- Logs: `storage/app/prod07-focused-tests.txt` and `storage/app/prod07-full-tests.txt`.

During development, a rollback assertion initially compared an unsynchronized
new Eloquent object's attributes against a database row; it now compares persisted
rows. One concurrent test run did not observe `LOCK WAIT` within its deadline.
The isolated rerun and final focused run passed; the worker handshake was then
moved after upload-fixture preparation so it announces the actual service attempt.
The final full suite passed with that synchronization change.

## Limits and recovery

- Process termination, filesystem outage during compensation, and ambiguous network
  failure during commit can leave orphan files. No distributed filesystem/DB
  transaction or durable cleanup queue is claimed.
- Operators should inspect `Product image cleanup failed; retry required` and
  `Image upload cleanup failed; retry required`, verify current references, and
  retry deletion of the recorded image and its thumbnail. Partial cleanup is
  possible when the filesystem fails; retry is idempotent.
- Query-builder/raw SQL writes, direct model path reassignment, and external file
  mutations bypass this service contract. Existing unknown orphans are not scanned.
- A lost file predating PROD-07 cannot be recovered by this change.

## Files changed for PROD-07

- `app/Services/ProductService.php`
- `app/Services/ProductImageOperation.php`
- `app/Services/ImageService.php`
- `app/Console/Commands/PurgeOldProducts.php`
- `tests/Feature/ProductImageLifecycleTest.php`
- `tests/Support/product-image-worker.php`
- `docs/product-image-lifecycle.md`

The working tree already contained PROD-06 changes when this task started; those
files were not edited as part of PROD-07.
