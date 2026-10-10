<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class OrderStockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Workers must see committed fixtures. The next test rebuilds the guarded DB.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_overlapping_mysql_cancellations_wait_and_restore_stock_exactly_once(): void
    {
        $this->runOverlappingCancellations();
    }

    public function test_exception_while_workers_hold_and_wait_for_lock_still_cleans_up(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Injected parent failure while workers are active');
        $this->runOverlappingCancellations(failWhileLocked: true);
    }

    private function runOverlappingCancellations(bool $failWhileLocked = false): void
    {
        $configuration = config()->getMany(['database', 'filesystems', 'backup']);
        $environment = [getenv('APP_ENV'), $_ENV['APP_ENV'] ?? null, $_SERVER['APP_ENV'] ?? null, getcwd()];
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create([
            'user_id' => $seller->id, 'title' => 'Concurrent stock', 'slug' => 'concurrent-stock',
            'price' => 100, 'stock' => 7,
        ]);
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id, 'number' => Order::generateNumber(),
            'status' => Order::STATUS_PENDING, 'total_price' => 300, 'currency' => 'PRB',
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 100, 'total' => 300]);

        $processes = [];
        $inputs = [];
        $milestones = [];
        try {
            foreach (['first', 'second'] as $mode) {
                $input = new InputStream();
                $input->write(json_encode(config('database'), JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/cancel-order-worker.php'), (string) $order->id, $mode], base_path(), timeout: 20);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $mode === 'first' ? 'LOCKED' : 'ATTEMPT');
                $milestones[$mode === 'first' ? 'first_locked' : 'second_attempt'] = microtime(true);
            }
            preg_match('/CONNECTION:(\d+)/', $processes[1]->getOutput(), $matches);
            $this->assertNotEmpty($matches, $matches ? '' : $this->lockWaitDiagnostics($processes, $milestones, []));
            $waiting = false;
            $deadline = microtime(true) + 8;
            $milestones['poll_started'] = microtime(true);
            $lastTransactions = [];
            do {
                $lastTransactions = DB::table('information_schema.INNODB_TRX')
                    ->where('trx_mysql_thread_id', $matches[1])
                    ->get(['trx_mysql_thread_id', 'trx_state', 'trx_wait_started'])
                    ->map(fn ($row) => (array) $row)->all();
                $milestones['last_poll'] = microtime(true);
                $waiting = collect($lastTransactions)->contains('trx_state', 'LOCK WAIT');
                if (! $waiting) {
                    usleep(50000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, $waiting ? '' : 'Second cancellation must actually wait on the first order row lock.'
                .$this->lockWaitDiagnostics($processes, $milestones, $lastTransactions));
            $this->assertStringNotContainsString('DONE', $processes[1]->getOutput());
            if ($failWhileLocked) {
                throw new \RuntimeException('Injected parent failure while workers are active');
            }
            $inputs[0]->write("release\n");
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $this->safeWorkerOutput($process->getErrorOutput()));
                $this->assertStringContainsString('DONE', $process->getOutput());
            }
            $this->assertSame(10, $product->fresh()->stock);
            $this->assertSame(Order::STATUS_CANCELED, $order->fresh()->status);
            $this->assertNotNull($order->fresh()->canceled_at);
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            $this->assertWorkersReleased($processes, $inputs);
            $this->assertSame($configuration, config()->getMany(['database', 'filesystems', 'backup']));
            $this->assertSame($environment, [getenv('APP_ENV'), $_ENV['APP_ENV'] ?? null, $_SERVER['APP_ENV'] ?? null, getcwd()]);
            $this->assertSame(0, DB::connection()->transactionLevel());
            $this->assertFalse(DB::connection()->getPdo()->inTransaction());
        }
    }

    private function assertWorkersReleased(array $processes, array $inputs): void
    {
        foreach ($inputs as $input) {
            $this->assertTrue($input->isClosed());
        }
        $connectionIds = [];
        foreach ($processes as $process) {
            $this->assertFalse($process->isRunning(), 'Cancellation worker survived cleanup.');
            if (preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $matches)) {
                $connectionIds[] = (int) $matches[1];
            }
        }
        // MySQL may need a short interval to observe a forcibly closed client socket.
        $deadline = microtime(true) + 5;
        do {
            $sessions = DB::table('information_schema.PROCESSLIST')->whereIn('ID', $connectionIds)->count();
            $transactions = DB::table('information_schema.INNODB_TRX')->whereIn('trx_mysql_thread_id', $connectionIds)->count();
            if ($sessions === 0 && $transactions === 0) {
                break;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);
        $this->assertSame(0, $sessions, 'Cancellation worker left a MySQL connection open.');
        $this->assertSame(0, $transactions, 'Cancellation worker left a transaction/lock behind.');
    }

    private function awaitOutput(Process $process, string $text): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $text) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($text, $process->getOutput(), $this->safeWorkerOutput($process->getErrorOutput()));
    }

    private function lockWaitDiagnostics(array $processes, array $milestones, array $lastTransactions): string
    {
        $workers = [];
        $connectionIds = [];
        foreach ($processes as $process) {
            $stdout = $process->getOutput();
            if (preg_match('/CONNECTION:(\d+)/', $stdout, $match)) {
                $connectionIds[] = (int) $match[1];
            }
            $workers[] = [
                'status' => $process->getStatus(),
                'exit_code' => $process->getExitCode(),
                'stdout' => $this->safeWorkerOutput($stdout),
                'stderr' => $this->safeWorkerOutput($process->getErrorOutput()),
            ];
        }
        try {
            $processlist = DB::table('information_schema.PROCESSLIST')->whereIn('ID', $connectionIds)
                ->get(['ID', 'COMMAND', 'TIME', 'STATE'])->map(fn ($row) => (array) $row)->all();
        } catch (\Throwable $exception) {
            $processlist = ['unavailable' => $exception::class];
        }
        $times = array_map(fn ($time) => date('c', (int) $time).' +'.round(($time - floor($time)) * 1000).'ms', $milestones);

        return "\nLock-wait diagnostics: ".json_encode([
            'times' => $times,
            'elapsed_poll_ms' => round((microtime(true) - ($milestones['poll_started'] ?? microtime(true))) * 1000),
            'workers' => $workers,
            'last_innodb_trx' => $lastTransactions,
            'processlist' => $processlist,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function safeWorkerOutput(string $output): string
    {
        foreach (config('database.connections', []) as $connection) {
            foreach (['password', 'url'] as $key) {
                $secret = is_array($connection) ? ($connection[$key] ?? null) : null;
                if (is_string($secret) && $secret !== '') {
                    $output = str_replace($secret, '[REDACTED]', $output);
                }
            }
        }

        return preg_replace([
            '~\b(?:mysql|mariadb)(?:://|:host=)[^\s]+~i',
            '~\b((?:DB_PASSWORD|PASSWORD|PWD)\s*[:=]\s*)[^\s,;]+~i',
            '~\bSQL:\s*[^\r\n]+~i',
        ], ['[REDACTED_DSN]', '$1[REDACTED]', 'SQL: [REDACTED]'], $output) ?? '[unavailable]';
    }
}
