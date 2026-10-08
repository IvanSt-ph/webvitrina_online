<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/** File compensation for one product transaction, including its parent savepoints. */
class ProductImageOperation
{
    private array $created = [];
    private bool $finished = false;

    public function __construct(private ImageService $images, private Connection $connection)
    {
        // Laravel discards committed child records on an outer rollback without running
        // their rollback callbacks. Attach compensation to every pending ancestor too.
        foreach (app('db.transactions')->getPendingTransactions() as $transaction) {
            if ($transaction->connection === $connection->getName()) {
                $transaction->addCallbackForRollback(fn () => $this->rollback());
            }
        }
        $connection->afterCommit(function () {
            $this->finished = true;
            $this->created = [];
        });
    }

    public function upload(UploadedFile $file, string $directory): string
    {
        return $this->created[] = $this->images->upload($file, $directory);
    }

    public function copy(string $source, string $destination): void
    {
        // Register before writing: a failed/partial copy must also be compensated.
        $this->created[] = $destination;
        if (! \Illuminate\Support\Facades\Storage::disk('public')->copy($source, $destination)) {
            throw new \RuntimeException('Unable to preserve order image.');
        }
    }

    public function deleteAfterCommit(?string $path): void
    {
        if ($path) {
            $this->connection->afterCommit(function () use ($path) {
                try {
                    // Existing shared main/gallery paths must survive. Application
                    // uploads always use new UUIDs; requests cannot reassign old paths.
                    if (Product::where('image', $path)->orWhereJsonContains('gallery', $path)->exists()) {
                        return;
                    }
                    $this->cleanup($path, 'after_commit');
                } catch (\Throwable $exception) {
                    Log::error('Product image cleanup failed; retry required', [
                        'path' => $path, 'phase' => 'reference_check', 'error' => $exception->getMessage(),
                    ]);
                }
            });
        }
    }

    public function rollback(): void
    {
        if ($this->finished) {
            return;
        }
        $this->finished = true;
        foreach ($this->created as $path) {
            $this->cleanup($path, 'rollback');
        }
        $this->created = [];
    }

    private function cleanup(string $path, string $phase): void
    {
        try {
            $this->images->delete($path, throwOnFailure: true);
        } catch (\Throwable $exception) {
            // Never turn a committed update into an apparent failure, or mask the
            // original rollback error. The path is retained in logs for manual retry.
            Log::error('Product image cleanup failed; retry required', [
                'path' => $path, 'phase' => $phase, 'error' => $exception->getMessage(),
            ]);
        }
    }
}
