<?php

namespace App\Console\Commands;

use App\Services\BackupStorageService;
use Illuminate\Console\Command;

class RestoreBackupFiles extends Command
{
    protected $signature = 'backup:restore-files
        {backup : Path to one completed backup directory}
        {--force : Confirm replacement of current public files and private chat images}';

    protected $description = 'Restore public storage and private chat images from a complete, checksum-valid backup.';

    public function handle(BackupStorageService $storage): int
    {
        if (! $this->option('force')) {
            $this->error('Restore refused. Pass --force after verifying the selected backup and database restore plan.');

            return self::FAILURE;
        }

        try {
            $storage->restore(
                (string) $this->argument('backup'),
                (string) config('filesystems.disks.public.root'),
                (string) config('filesystems.disks.local.root'),
            );

            $this->info('Public storage and private chat images restored successfully.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
