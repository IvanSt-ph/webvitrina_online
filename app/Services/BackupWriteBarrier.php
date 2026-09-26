<?php

namespace App\Services;

/** Process-owned locks: no expiring lease and no maintenance-state mutation. */
class BackupWriteBarrier
{
    private mixed $handle = null;
    private int $depth = 0;
    private bool $exclusive = false;

    public function acquire(bool $exclusive = false): void
    {
        if ($this->depth > 0) {
            if ($exclusive && ! $this->exclusive) {
                throw new \RuntimeException('Backup cannot start inside an active file mutation.');
            }
            $this->depth++;
            return;
        }
        $path = (string) config('backup.lock_path');
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open backup write barrier: ' . $path);
        }
        // Never unlink this inode: replacing it would create two independent locks.
        $deadline = microtime(true) + (int) config('backup.lock_timeout', 60);
        do {
            if (flock($handle, ($exclusive ? LOCK_EX : LOCK_SH) | LOCK_NB)) {
                $this->handle = $handle;
                $this->exclusive = $exclusive;
                $this->depth = 1;
                return;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        fclose($handle);
        throw new \RuntimeException('Backup write barrier busy; retry after the current backup/mutation completes.');
    }

    public function release(): void
    {
        if ($this->depth === 0 || --$this->depth > 0) {
            return;
        }
        fclose($this->handle);
        $this->handle = null;
        $this->exclusive = false;
    }

    public function run(callable $callback, bool $exclusive = false): mixed
    {
        $this->acquire($exclusive);
        try {
            return $callback();
        } finally {
            $this->release();
        }
    }

    /** Keep a service mutation protected through an enclosing caller transaction. */
    public function transaction(callable $callback): mixed
    {
        $this->acquire();
        $deferred = false;
        try {
            return \Illuminate\Support\Facades\DB::transaction($callback);
        } finally {
            $connection = \Illuminate\Support\Facades\DB::connection();
            if ($connection->transactionLevel() > 0) {
                $released = false;
                $release = function () use (&$released) {
                    if (! $released) {
                        $released = true;
                        $this->release();
                    }
                };
                // Registered after file callbacks, so release follows their cleanup.
                $connection->afterCommit($release);
                foreach (app('db.transactions')->getPendingTransactions() as $transaction) {
                    if ($transaction->connection === $connection->getName()) {
                        $transaction->addCallbackForRollback($release);
                    }
                }
                $deferred = true;
            }
            if (! $deferred) {
                $this->release();
            }
        }
    }
}
