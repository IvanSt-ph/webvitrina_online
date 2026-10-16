<?php

namespace App\Console\Commands;

use App\Services\BackupStorageService;
use App\Support\BackupHealth;
use Illuminate\Console\Command;

class RestoreBackupFiles extends Command
{
    protected $signature = 'backup:restore-files
        {backup : Path to one completed backup directory}
        {--drill= : Existing empty absolute directory for an isolated restore (fixed public/private children)}
        {--force : Confirm replacement of current public files and private chat/review images}';

    protected $description = 'Restore public storage and private chat/review images from a complete, checksum-valid backup.';

    public function handle(BackupStorageService $storage): int
    {
        $drill = $this->option('drill');
        if ($drill !== null && $this->option('force')) {
            $this->error('Choose isolated --drill or destructive --force, never both.');
            return self::FAILURE;
        }
        if ($drill === null && ! $this->option('force')) {
            $this->error('Restore refused. Pass --force after verifying the selected backup and database restore plan.');

            return self::FAILURE;
        }

        try {
            [$public, $private] = $drill !== null
                ? app(\App\Services\RestoreDestination::class)->isolated((string) $drill, (string) $this->argument('backup'))
                : [(string) config('filesystems.disks.public.root'), (string) config('filesystems.disks.local.root')];
            $storage->restore(
                (string) $this->argument('backup'),
                $public,
                $private,
            );

            $this->info('Public storage and private chat/review images restored successfully.');
            $backup = (string) $this->argument('backup');
            if ((int) (BackupHealth::inspectDirectory($backup, false)['manifest']['version'] ?? 0) < 3) {
                $this->warn('Legacy v2 backup restored. Review images remain public until review-images:migrate-private --apply --delete-public completes.');
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
