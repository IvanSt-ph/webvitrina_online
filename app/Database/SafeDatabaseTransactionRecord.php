<?php

namespace App\Database;

use Closure;
use Illuminate\Database\DatabaseTransactionRecord;
use LogicException;

/** Defers user code until the manager has completed its state transition. */
class SafeDatabaseTransactionRecord extends DatabaseTransactionRecord
{
    public function __construct(
        $connection,
        $level,
        ?DatabaseTransactionRecord $parent = null,
        private readonly ?Closure $enqueue = null,
    ) {
        parent::__construct($connection, $level, $parent);
    }

    public function executeCallbacks()
    {
        $this->queueCallbacks($this->callbacks);
    }

    public function executeCallbacksForRollback()
    {
        $this->queueCallbacks($this->callbacksForRollback);
    }

    private function queueCallbacks(array $callbacks): void
    {
        if ($this->enqueue === null) {
            throw new LogicException('Transaction record requires its owning safe manager.');
        }

        ($this->enqueue)($callbacks);
        // A completed record cannot replay either outcome, even if retained by a caller.
        $this->callbacks = [];
        $this->callbacksForRollback = [];
    }
}
