<?php

namespace App\Console\Commands;

use App\Services\BackupWriteBarrier;
use App\Services\OrderIdentitySnapshot;
use Illuminate\Console\Command;

class BackfillOrderIdentity extends Command
{
    protected $signature = 'orders:backfill-identity';
    protected $description = 'Capture available legacy order identity (not guaranteed purchase-time data)';

    public function handle(OrderIdentitySnapshot $snapshots): int
    {
        app(BackupWriteBarrier::class)->run(fn () => $snapshots->backfill());
        $this->info('Available legacy identities captured; existing snapshots were not overwritten.');
        return self::SUCCESS;
    }
}
