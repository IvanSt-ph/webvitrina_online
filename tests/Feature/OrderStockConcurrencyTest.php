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
            }
            preg_match('/CONNECTION:(\d+)/', $processes[1]->getOutput(), $matches);
            $this->assertNotEmpty($matches);
            $waiting = false;
            $deadline = microtime(true) + 8;
            do {
                $waiting = DB::table('information_schema.INNODB_TRX')
                    ->where('trx_mysql_thread_id', $matches[1])->where('trx_state', 'LOCK WAIT')->exists();
                if (! $waiting) {
                    usleep(50000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'Second cancellation must actually wait on the first order row lock.');
            $this->assertStringNotContainsString('DONE', $processes[1]->getOutput());
            if ($failWhileLocked) {
                throw new \RuntimeException('Injected parent failure while workers are active');
            }
            $inputs[0]->write("release\n");
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
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
        $this->assertStringContainsString($text, $process->getOutput(), $process->getErrorOutput());
    }
}
