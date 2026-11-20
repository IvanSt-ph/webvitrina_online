# Transaction manager compatibility

The strict reviewed Laravel version is 12.71.0. The official 12.69.2 and
12.71.0 sources of DatabaseTransactionsManager, DatabaseTransactionRecord,
ManagesTransactions, Connection, DatabaseManager and Queue are identical
after newline normalization. The adapter algorithm is unchanged.

The adapter finishes internal state transitions before running user callbacks.
It attempts all callbacks and logging; commit rethrows the first callback error,
whereas rollback preserves the original application error. A callback error
after PDO commit does not mean that database changes were rolled back.

## Multiple connections

State finalization is connection-specific, but inherited Laravel callback
registration selects the last pending transaction globally. Connection::afterCommit
does not pass its connection name to the manager. The pure Connection API test
documents this limitation; simultaneous transactional connections are NOT fully
supported. The existing state-isolation test registers directly on records and
does not prove registration isolation.

Production call sites reviewed use the default connection for transactional
business operations and file callbacks. The separate current-password database
cache connection finishes its synchronous cache transaction before returning to
business code; it does not register these callbacks. Backup snapshot PDO is
independent of this manager. Session/health connections do not introduce an
overlapping business transaction callback path. No reachable misregistration
was identified in the reviewed code; future cross-connection orchestration must
be reviewed explicitly.

## Validation and future upgrades

Run SafeDatabaseTransactionsManagerTest without a database first. Then run
TransactionCallbackContractTest only through run-safe-feature-tests.php in
existing-schema mode, with explicit cleanup acknowledgement. It requires real
commits/savepoints, guarded probe connections, isolated files and unique queue
fixtures; do not wrap it in an outer rollback transaction.

Unknown framework versions remain rejected. Repeat the source comparison and
contract tests before changing the exact approved version again. Do not infer
compatibility from signatures alone or disable the guard.
