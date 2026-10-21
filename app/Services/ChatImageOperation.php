<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Log;

/** A unique new file belongs to this operation until the outer commit. */
class ChatImageOperation
{
    private bool $attached = false;
    private bool $finished = false;

    public function __construct(private readonly ?string $createdPath)
    {
    }

    public function attach(Connection $connection): void
    {
        if ($connection->transactionLevel() === 0) {
            throw new \LogicException('Chat image operation requires a transaction.');
        }
        // Laravel discards committed child records on an ancestor rollback;
        // their own rollback callbacks alone would miss that rollback.
        foreach (app('db.transactions')->getPendingTransactions() as $transaction) {
            if ($transaction->connection === $connection->getName()) {
                $transaction->addCallbackForRollback(fn () => $this->rollback());
            }
        }
        $connection->afterCommit(function (): void {
            $this->finished = true;
        });
        $this->attached = true;
    }

    public function handleFailure(): void
    {
        // Once attached, only an actual rollback may remove the new file.
        // An exception from an afterCommit callback is NOT a rollback.
        if (! $this->attached) {
            $this->rollback();
        } elseif (! $this->finished && $this->createdPath !== null) {
            // A connection/commit failure can have an unknown outcome. Retain
            // the bytes rather than risk deleting a committed attachment.
            Log::error('Chat image transaction outcome unknown; reconciliation required', [
                'path' => $this->createdPath,
            ]);
        }
    }

    private function rollback(): void
    {
        if (! $this->finished) {
            $this->finished = true;
            if ($this->createdPath !== null) {
                app(ChatImageService::class)->cleanup($this->createdPath, 'rollback');
            }
        }
    }

    public static function deleteAfterCommit(Connection $connection, ?string $path): void
    {
        if (! $path) {
            return;
        }
        $connection->afterCommit(function () use ($path): void {
            try {
                if (! Message::query()->useWritePdo()->where('image_path', $path)->exists()) {
                    app(ChatImageService::class)->cleanup($path, 'after_commit');
                }
            } catch (\Throwable $exception) {
                Log::error('Chat image cleanup failed; retry required', [
                    'path' => $path, 'phase' => 'after_commit', 'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
