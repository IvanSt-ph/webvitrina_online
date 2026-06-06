<?php

namespace App\Console\Commands;

use App\Support\BackupHealth;
use Illuminate\Console\Command;

class BackupHealthCheck extends Command
{
    protected $signature = 'backup:health-check
        {--path= : Backup directory to inspect}
        {--max-age-hours= : Maximum accepted age for the latest backup}
        {--skip-checksums : Do not verify SHA256SUMS contents}';

    protected $description = 'Verify that the latest backup is fresh, complete, and checksum-valid.';

    public function handle(): int
    {
        $health = BackupHealth::latest(
            $this->option('path') ?: null,
            $this->option('max-age-hours') !== null ? (int) $this->option('max-age-hours') : null,
            ! $this->option('skip-checksums')
        );

        if (! $health['latest']) {
            $this->error('Backup not found.');
            $this->line('Path: ' . $health['path']);

            return self::FAILURE;
        }

        $this->line('Latest backup: ' . $health['created_at']);
        $this->line('Directory: ' . $health['latest_name']);
        $this->line('Age: ' . $health['age_hours'] . ' hours');

        foreach ($health['files'] as $file => $info) {
            $this->line(($info['exists'] ? '[ok] ' : '[missing] ') . $file . ' (' . $info['size_human'] . ')');
        }

        if ($health['ok']) {
            $this->info('Backup health-check passed.');

            return self::SUCCESS;
        }

        $this->error('Backup health-check failed.');

        foreach ($health['issues'] as $issue) {
            $this->line('- ' . $issue);
        }

        return self::FAILURE;
    }
}
