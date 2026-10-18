<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/** Transaction-aware lifecycle for profile avatars. */
class ProfileAvatarOperation
{
    private array $created = [];

    private bool $finished = false;

    public function __construct(
        private readonly ImageService $images,
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

    public function upload(UploadedFile $file): string
    {
        return $this->created[] = $this->images->upload($file, 'avatars');
    }

    public function deleteAfterCommit(?string $path): void
    {
        if (!$path) {
            return;
        }

        $this->connection->afterCommit(function () use ($path): void {
            try {
                if (User::query()->where('avatar', $path)->exists()) {
                    return;
                }

                $this->images->delete($path, throwOnFailure: true);
            } catch (\Throwable $exception) {
                Log::error('Profile avatar cleanup failed; retry required', [
                    'path' => $path,
                    'phase' => 'after_commit',
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    public function rollback(): void
    {
        if ($this->finished) {
            return;
        }

        $this->finished = true;
        foreach ($this->created as $path) {
            try {
                $this->images->delete($path, throwOnFailure: true);
            } catch (\Throwable $exception) {
                Log::error('Profile avatar cleanup failed; retry required', [
                    'path' => $path,
                    'phase' => 'rollback',
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        $this->created = [];
    }
}
