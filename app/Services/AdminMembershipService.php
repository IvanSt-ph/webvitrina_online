<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Serialize membership reductions before acquiring any target User row lock. */
class AdminMembershipService
{
    public function transaction(callable $operation): mixed
    {
        $connection = DB::connection();
        $name = 'wv:admins:'.substr(hash('sha256', $connection->getDatabaseName()), 0, 48);
        $lock = $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$name]);
        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages(['user' => 'Не удалось изменить состав администраторов. Повторите попытку.']);
        }

        $released = false;
        $release = function () use ($connection, $name, &$released): void {
            if (! $released) {
                $released = true;
                $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
            }
        };
        try {
            return $connection->transaction($operation);
        } finally {
            // GET_LOCK is connection-owned, not transaction-owned. In particular,
            // a nested savepoint commit must not expose uncommitted membership.
            if ($connection->transactionLevel() > 0) {
                $connection->afterCommit($release);
                foreach (app('db.transactions')->getPendingTransactions() as $transaction) {
                    if ($transaction->connection === $connection->getName()) {
                        $transaction->addCallbackForRollback($release);
                    }
                }
            } else {
                $release();
            }
        }
    }

    /** The caller holds both the membership lock and the current target row. */
    public function assertCanRemove(User $current, string $field = 'user'): void
    {
        if ($current->role !== 'admin' || $current->trashed()) {
            return;
        }

        // A locking read sees committed membership even if an enclosing REPEATABLE
        // READ transaction established its snapshot before waiting for the mutex.
        $other = User::query()->where('role', 'admin')->whereKeyNot($current->getKey())
            ->lockForUpdate()->first(['id']);
        if ($other === null) {
            throw ValidationException::withMessages([
                $field => $field === 'role'
                    ? 'Нельзя убрать роль администратора у последнего администратора.'
                    : 'Нельзя удалить последнего администратора.',
            ]);
        }
    }
}
