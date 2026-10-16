<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/** Transaction-aware lifecycle for private review images. */
class ReviewImageOperation
{
    private array $created = [];

    private bool $finished = false;

    public function __construct(
        private readonly ImageService $images,
        private readonly ReviewImageStorage $storage,
        private readonly Connection $connection,
    ) {
        foreach (app('db.transactions')->getPendingTransactions() as $transaction) {
            if ($transaction->connection === $connection->getName()) {
                $transaction->addCallbackForRollback(fn () => $this->rollback());
            }
        }

        $connection->afterCommit(function (): void {
            $this->finished = true;
            $this->created = [];
        });
    }

    /** @param UploadedFile[] $files */
    public function uploadGallery(array $files, string $directory): array
    {
        $paths = $this->images->uploadGallery($files, $directory, ReviewImageStorage::DISK);
        array_push($this->created, ...$paths);

        return $paths;
    }

    public function deleteAfterCommit(?string $path): void
    {
        if (!$path) {
            return;
        }

        $this->connection->afterCommit(fn () => $this->cleanupEverywhere($path, 'after_commit'));
    }

    public function rollback(): void
    {
        if ($this->finished) {
            return;
        }

        $this->finished = true;
        foreach ($this->created as $path) {
            try {
                $this->images->delete($path, throwOnFailure: true, diskName: ReviewImageStorage::DISK);
            } catch (\Throwable $exception) {
                Log::error('Review image cleanup failed; retry required', [
                    'path' => $path,
                    'phase' => 'rollback',
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        $this->created = [];
    }

    private function cleanupEverywhere(string $path, string $phase): void
    {
        try {
            $this->storage->deleteIfUnreferenced($path, throwOnFailure: true);
        } catch (\Throwable $exception) {
            Log::error('Review image cleanup failed; retry required', [
                'path' => $path,
                'phase' => $phase,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
