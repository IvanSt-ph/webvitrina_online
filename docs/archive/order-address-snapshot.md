# PROD-06: immutable order delivery address

## Storage and checkout

Previously `orders.address_id` referenced a mutable address-book row with `ON DELETE SET NULL`.
Buyer, seller and admin detail views read that row; seller/admin preferred the existing
nullable `orders.delivery_address` text. Address updates and deletions were already allowed.
PROD-04 retained referenced address rows during account deletion, but did not freeze them.

The actual address fields are `country`, `city`, `street`, `house`, `entrance`, `apartment`,
`postal_code`, `comment`. Country/city are strings, not foreign keys to location records.
`is_default` is address-book metadata. There is no separate recipient/telephone field in
an address: PROD-04 already captures name/email/phone in `orders.buyer_contact`.

`orders.address_snapshot` is JSON with an Eloquent array cast. One order-owned document
keeps the eight optional address fields together with `full`, the display string.
This follows the existing `buyer_contact` snapshot approach without eight new columns
or a second table. `address_id`, its FK and `delivery_address` remain for compatibility.

The Order creating hook reads the address from the database, scopes it to the buyer,
and locks it for update. Checkout already creates all seller orders inside one database
transaction, so every snapshot and item commits or rolls back together. The lock lasts
through the entire multi-seller checkout. The existing checkout validation rejects foreign
IDs, and the model checks ownership again against persisted data. Client snapshot input
is not fillable and never used. Pickup without an address records an empty address.

## Migration and display

Migration `2026_09_25_000001_add_address_snapshot_to_orders.php` adds the nullable JSON column
and backfills in batches of 200. Existing order-local `delivery_address` takes priority
for the display string; available address-book fields and comment are copied. Missing
data stays empty. There is no live relation fallback. A repeated migration run fills only
null snapshots and never replaces captured history. Order timestamps, items and money
are untouched. Destructive rollback is refused because deleted address-book rows could
no longer reconstruct the snapshot.

Buyer/seller/admin detail views use only snapshot fields. Order lists do not display
delivery addresses. The audited application has no order invoice/print/export/API resource
or email body rendering a delivery address. Eager loading of the historical address relation
was removed from order pages and the profile cabinet.

Account deletion now removes all address-book rows. The existing FK clears `address_id`;
orders, items, buyer contact and delivery snapshots remain. Other PROD-04 behavior is unchanged.

## Immutability and operational limits

Model saves reject changes to an existing `address_snapshot`, including `saveQuietly()`;
the field is excluded from mass assignment. Status/cancellation actions do not rewrite it.
Query-builder bulk writes, raw SQL and event-free insertion are privileged bypasses of
model lifecycle rules and must not be used to replace/create business snapshots.

Run the migration with checkout/address/account writes paused, then deploy the matching
application code before reopening writes. The batched backfill does not coordinate with
old application instances mutating addresses during deployment. This migration has not
been run against the production database by this task.

Data changed or deleted before migration cannot be recovered as the original checkout
address without an external historical source. Backfill preserves the best currently
available data, not a claim to recover previously lost history. The unused `addresses()`
relations on Country/City imply FK columns absent from the actual address table; correcting
those unrelated relations is outside PROD-06.

## Verification

`OrderAddressSnapshotTest` covers real multi-seller checkout, all address fields, forged
client snapshot input, address/profile edits, address deletion, all three viewer roles,
status/cancellation and monetary preservation, model immutability and no relation fallback.
`OrderAddressSnapshotMigrationTest` covers real MySQL schema upgrade, structured/text-only/
missing legacy data, preservation of other columns, repeated backfill and deletion afterward.
`AccountDeletionTest` now expects address-book deletion and only the FK to become null;
its existing history, access revocation and re-registration assertions remain in place.
Existing checkout ownership, monetary and stock/cancellation suites are also required.

Verified on the guarded MySQL `webv3_testing` database:

- Focused regression run: 25 passed, 387 assertions.
- Follow-up after strengthening seller/admin endpoint and deleted-account rendering checks:
  2 passed, 88 assertions.
- Full `php artisan test`: 384 passed, 2770 assertions (347.39 seconds).
- `php -l`: all 13 changed/new PHP and Blade files passed; all three compiled Blade
  templates also passed syntax checks.
- `git diff --check`: passed.

Changed files:

- `app/Models/Order.php`
- `app/Services/AccountDeletionService.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/Seller/OrderController.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `app/Http/Controllers/ProfileController.php`
- `database/migrations/2026_09_25_000001_add_address_snapshot_to_orders.php`
- `resources/views/shop/order-show.blade.php`
- `resources/views/seller/orders/show.blade.php`
- `resources/views/admin/orders/show.blade.php`
- `tests/Feature/AccountDeletionTest.php`
- `tests/Feature/OrderAddressSnapshotTest.php`
- `tests/Feature/OrderAddressSnapshotMigrationTest.php`
- `docs/order-address-snapshot.md`
