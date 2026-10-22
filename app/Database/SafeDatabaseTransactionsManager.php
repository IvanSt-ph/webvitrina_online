<?php

namespace App\Database;

use Closure;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

/** Laravel 12.69.2 adapter: finish manager state transitions before invoking user code. */
class SafeDatabaseTransactionsManager extends DatabaseTransactionsManager
{
    /** @var array<int, array<int, callable>> */
    private array $contexts = [];

    private readonly Closure $reportFailure;

    public function __construct(?Closure $reportFailure = null)
    {
        if (Application::VERSION !== '12.69.2') {
            throw new LogicException('Re-audit transaction callback compatibility before upgrading Laravel.');
        }
        parent::__construct();
        $this->reportFailure = $reportFailure ?? static function (Throwable $exception, array $context): void {
            Log::error('Transaction callback failed.', $context + ['exception' => $exception]);
        };
    }

    public function begin($connection, $level)
    {
        $transaction = new SafeDatabaseTransactionRecord(
            $connection,
            $level,
            $this->currentTransaction[$connection] ?? null,
            function (array $callbacks): void {
                $index = array_key_last($this->contexts);
                if ($index === null) {
                    throw new LogicException('Callbacks must be finalized by their owning manager.');
                }
                array_push($this->contexts[$index], ...$callbacks);
            },
        );
        $this->pendingTransactions->push($transaction);
        $this->currentTransaction[$connection] = $transaction;
    }

    public function commit($connection, $levelBeingCommitted, $newTransactionLevel)
    {
        return $this->finalize(
            fn () => parent::commit($connection, $levelBeingCommitted, $newTransactionLevel),
            'commit', $connection,
        );
    }

    public function rollback($connection, $newTransactionLevel)
    {
        $this->finalize(
            fn () => parent::rollback($connection, $newTransactionLevel),
            'rollback', $connection,
        );
    }

    private function finalize(Closure $transition, string $phase, $connection): mixed
    {
        $this->contexts[] = [];
        try {
            $result = $transition();
        } finally {
            $callbacks = array_pop($this->contexts);
        }

        // State is already finalized. Reentrant transactions get independent queues
        // and cannot inherit this transaction's parent or failure list.
        $failures = [];
        foreach ($callbacks as $callback) {
            try {
                $callback();
            } catch (Throwable $exception) {
                $failures[] = $exception;
            }
        }
        foreach ($failures as $exception) {
            try {
                ($this->reportFailure)($exception, ['phase' => $phase, 'connection' => $connection]);
            } catch (Throwable) {
                // Reporting must never replace an original error or stop finalization.
            }
        }
        if ($phase === 'commit' && $failures !== []) {
            // PDO may already have committed. Never roll back or retry here.
            throw $failures[0];
        }
        // Laravel's rollback caller owns the original business exception. Throwing
        // a cleanup error here would replace it (also applies to explicit rollback).
        return $result;
    }
}
