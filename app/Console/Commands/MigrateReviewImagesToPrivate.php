<?php

namespace App\Console\Commands;

use App\Models\ReviewImage;
use App\Services\BackupWriteBarrier;
use App\Services\ImageService;
use App\Services\ReviewImageStorage;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateReviewImagesToPrivate extends Command
{
    protected $signature = 'review-images:migrate-private
        {--apply : Copy and verify files; without this option no files are changed}
        {--delete-public : After a completely successful verification pass, remove verified legacy public copies}';

    protected $description = 'Safely migrate review images from public storage to private storage';

    public function handle(BackupWriteBarrier $barrier, ReviewImageStorage $paths): int
    {
        if ($this->option('delete-public') && !$this->option('apply')) {
            $this->error('--delete-public requires --apply.');

            return self::FAILURE;
        }

        return $barrier->run(fn (): int => $this->migrate($paths), exclusive: true);
    }

    private function migrate(ReviewImageStorage $paths): int
    {
        $apply = (bool) $this->option('apply');
        $deletePublic = (bool) $this->option('delete-public');
        $public = Storage::disk(ReviewImageStorage::LEGACY_DISK);
        $private = Storage::disk(ReviewImageStorage::DISK);
        if ($apply && $private->directoryExists('.migration') && !$private->deleteDirectory('.migration')) {
            $this->error('Unable to remove an incomplete private migration staging directory.');

            return self::FAILURE;
        }
        $verifiedPublic = [];
        $stats = ['records' => 0, 'copied' => 0, 'verified' => 0, 'private_only' => 0, 'missing_thumbs' => 0, 'orphan_public' => 0, 'errors' => 0];

        ReviewImage::query()->orderBy('id')->chunkById(200, function ($images) use (
            $paths, $public, $private, $apply, &$verifiedPublic, &$stats
        ): void {
            foreach ($images as $image) {
                $stats['records']++;
                $medium = $paths->path($image, 'medium');
                if ($medium === null) {
                    $stats['errors']++;
                    $this->error("review_image={$image->id}: unsafe path");
                    continue;
                }

                $this->inspectFile($medium, true, $public, $private, $apply, $verifiedPublic, $stats, $image->id);
                $this->inspectFile(ImageService::thumbPath($medium), false, $public, $private, $apply, $verifiedPublic, $stats, $image->id);
            }
        });

        if ($deletePublic) {
            $orphans = array_values(array_diff($public->allFiles('reviews'), array_keys($verifiedPublic)));
            $stats['orphan_public'] = count($orphans);
            if ($orphans !== []) {
                $stats['errors']++;
                $this->error('Unreferenced public review files found: '.count($orphans).'. No public files were deleted.');
            }
        }

        if ($stats['errors'] > 0) {
            $this->error("Migration stopped with {$stats['errors']} error(s). Public files were not deleted.");
            $this->summary($stats, $apply);

            return self::FAILURE;
        }

        if ($deletePublic) {
            foreach (array_keys($verifiedPublic) as $path) {
                if ($public->exists($path) && !$public->delete($path)) {
                    $this->error("Unable to delete verified public file: {$path}");

                    return self::FAILURE;
                }
            }
            $this->info('Verified public copies deleted: '.count($verifiedPublic));
        } elseif ($apply) {
            $this->warn('Private copies are verified, but legacy /storage/reviews URLs remain public until --delete-public succeeds.');
        }

        $this->summary($stats, $apply);

        return self::SUCCESS;
    }

    private function inspectFile(
        string $path,
        bool $required,
        FilesystemAdapter $public,
        FilesystemAdapter $private,
        bool $apply,
        array &$verifiedPublic,
        array &$stats,
        int $imageId,
    ): void {
        $hasPublic = $public->exists($path);
        $hasPrivate = $private->exists($path);

        if (!$hasPublic && !$hasPrivate) {
            if ($required) {
                $stats['errors']++;
                $this->error("review_image={$imageId}: missing medium file {$path}");
            } else {
                $stats['missing_thumbs']++;
            }
            return;
        }

        if (!$hasPublic) {
            try {
                $this->hash($private, $path);
                $stats['private_only']++;
            } catch (\Throwable $exception) {
                $stats['errors']++;
                $this->error("review_image={$imageId}: {$exception->getMessage()}");
            }
            return;
        }

        if (!$hasPrivate) {
            if (!$apply) {
                try {
                    $this->hash($public, $path);
                    $stats['copied']++;
                } catch (\Throwable $exception) {
                    $stats['errors']++;
                    $this->error("review_image={$imageId}: {$exception->getMessage()}");
                }
                return;
            }

            try {
                $this->copyVerified($public, $private, $path);
                $stats['copied']++;
                $hasPrivate = true;
            } catch (\Throwable $exception) {
                $stats['errors']++;
                $this->error("review_image={$imageId}: {$exception->getMessage()}");
                return;
            }
        }

        if ($hasPrivate) {
            try {
                if (!hash_equals($this->hash($public, $path), $this->hash($private, $path))) {
                    throw new \RuntimeException("public/private checksum mismatch for {$path}");
                }
                $stats['verified']++;
                $verifiedPublic[$path] = true;
            } catch (\Throwable $exception) {
                $stats['errors']++;
                $this->error("review_image={$imageId}: {$exception->getMessage()}");
            }
        }
    }

    private function copyVerified(FilesystemAdapter $public, FilesystemAdapter $private, string $path): void
    {
        $temporary = '.migration/'.Str::uuid().'.part';
        $input = $public->readStream($path);
        if (!is_resource($input)) {
            throw new \RuntimeException("cannot read public file {$path}");
        }

        try {
            if (!$private->writeStream($temporary, $input)) {
                throw new \RuntimeException("cannot write private temporary file for {$path}");
            }
        } finally {
            fclose($input);
        }

        try {
            if (!hash_equals($this->hash($public, $path), $this->hash($private, $temporary))) {
                throw new \RuntimeException("checksum verification failed for {$path}");
            }
            if (!$private->move($temporary, $path)) {
                throw new \RuntimeException("cannot publish verified private file {$path}");
            }
        } finally {
            if ($private->exists($temporary)) {
                $private->delete($temporary);
            }
        }
    }

    private function hash(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (!is_resource($stream)) {
            throw new \RuntimeException("cannot read {$path} for checksum");
        }

        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    private function summary(array $stats, bool $apply): void
    {
        $mode = $apply ? 'APPLY' : 'DRY-RUN';
        $this->info(sprintf(
            '%s: records=%d copied_or_planned=%d verified=%d private_only=%d missing_optional_thumbs=%d orphan_public=%d errors=%d',
            $mode,
            $stats['records'],
            $stats['copied'],
            $stats['verified'],
            $stats['private_only'],
            $stats['missing_thumbs'],
            $stats['orphan_public'],
            $stats['errors'],
        ));
    }
}
