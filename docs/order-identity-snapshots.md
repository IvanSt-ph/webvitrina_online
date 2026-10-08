# Order identity snapshots

Apply the additive migration during the normal coordinated release; do not run it
against a live database without the release procedure. Then run
`php artisan orders:backfill-identity` with the application writers stopped for a
consistent legacy capture. The command is resumable and only fills uninitialized
snapshots. It is deliberately separate from schema migration (filesystem work and
potentially large data sets). Neither command was run on the working database by
the implementation task.

New checkout items capture title, SKU and an independent copy of the primary image
from the locked server-side Product in the checkout transaction. The order stores
seller/shop names once. Money, currency, buyer/address snapshots and foreign keys
are unchanged. Model saves, including quiet saves, reject snapshot changes;
privileged raw SQL/query-builder writes remain an operator boundary.

Historical images use UUID paths in `storage/app/public/order-snapshots`. They are
included automatically in the existing public-storage backup and restore. Product
replacement/deletion operates on the original product paths, never these copies.
Rollback compensation is attached to all enclosing transactions. Missing original
images produce a placeholder; existing-image copy failures abort the transaction.
A process crash can leave an unreferenced copy; no automatic destructive GC is
introduced. Retain snapshots for the lifetime of the orders and monitor disk space.

Legacy backfill records current available data, not proven purchase-time data.
Its `legacy_backfill` provenance is displayed with an explicit warning. Missing
products are marked `unavailable`; unavailable images are not invented. Re-running
the command never refreshes already captured data. Source backups/audit records
would be needed to recover a genuinely older title or photograph.

Product links and operational stock still use the current Product. Historical
titles/images/SKU, order search and order-context messages use snapshots. Already
stored message bodies remain unchanged historical communication.
