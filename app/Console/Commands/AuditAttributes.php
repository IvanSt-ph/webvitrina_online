<?php

namespace App\Console\Commands;

use App\Services\AttributeDuplicateCleanup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AuditAttributes extends Command
{
    protected $signature = 'attributes:audit {--database= : Explicit database name} {--apply= : Reviewed dry-run fingerprint; test databases only}';

    protected $description = 'Read-only duplicate/history report; optional reviewed merge in a test database';

    public function handle(AttributeDuplicateCleanup $cleanup): int
    {
        $target = (string) $this->option('database');
        if (! in_array($target, ['webv3_testing', 'webv3_attributes_test'], true)) {
            $this->error('Specify webv3_testing or webv3_attributes_test. No implicit default database.');

            return self::FAILURE;
        }
        $config = config('database.connections.mysql');
        if (! empty($config['url']) || ! empty($config['read']) || ! empty($config['write'])) {
            throw new RuntimeException('DB URL/read/write overrides are not allowed.');
        }
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => $target, 'cache.default' => 'array']);
        DB::purge('mysql');
        if (DB::selectOne('SELECT DATABASE() AS name')->name !== $target) {
            throw new RuntimeException('Actual database mismatch.');
        }
        if ($fingerprint = $this->option('apply')) {
            $this->info('Merged duplicate definitions: '.$cleanup->apply((string) $fingerprint));
        } else {
            DB::statement('SET TRANSACTION READ ONLY');
            DB::beginTransaction();
            try {
                $this->line(json_encode($cleanup->snapshot(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            } finally {
                DB::rollBack();
            }
        }

        return self::SUCCESS;
    }
}
