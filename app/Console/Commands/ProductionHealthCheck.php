<?php

namespace App\Console\Commands;

use App\Support\ProductionHealth;
use Illuminate\Console\Command;

class ProductionHealthCheck extends Command
{
    protected $signature = 'production:health-check {--json : Output machine-readable JSON}';

    protected $aliases = ['production:health'];

    protected $description = 'Check production dependencies; exit 0 healthy, 1 warning, 2 critical/unavailable.';

    public function handle(ProductionHealth $health): int
    {
        $checks = $health->operational();
        $critical = collect($checks)->contains(fn ($check) => ! in_array($check['status'], [ProductionHealth::PASS, ProductionHealth::WARNING], true));
        $warning = collect($checks)->contains('status', ProductionHealth::WARNING);
        $exit = $critical ? 2 : ($warning ? 1 : 0);

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $critical ? 'critical' : ($warning ? 'warning' : 'ok'),
                'checked_at' => now()->toIso8601String(),
                'checks' => $checks,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Check', 'Status', 'Detail'], collect($checks)->map(
                fn ($check, $name) => [$name, $check['status'], $check['detail']]
            )->values()->all());
        }

        return $exit;
    }
}
