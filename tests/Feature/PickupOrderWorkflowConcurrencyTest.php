<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PickupOrderWorkflowConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Child processes must see committed fixtures.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_payment_and_receipt_wait_on_order_lock_and_complete_once(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'total_price' => 100, 'currency' => 'PRB',
        ]);
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'workflow_version' => Order::WORKFLOW_PICKUP,
            'payment_status' => Order::PAYMENT_UNPAID,
            'ready_for_pickup_at' => now(),
        ]);

        $processes = [];
        $inputs = [];
        $connection = DB::connection();
        try {
            $connection->beginTransaction();
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            foreach ([['payment', $seller], ['receipt', $buyer]] as [$action, $actor]) {
                $input = new InputStream();
                $input->write(json_encode(config('database'), JSON_THROW_ON_ERROR)."\n");
                $process = new Process([
                    PHP_BINARY, base_path('tests/Support/pickup-order-worker.php'),
                    (string) $order->id, (string) $actor->id, $action,
                ], base_path(), timeout: 20);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, 'ATTEMPT');
            }

            $waiting = false;
            $deadline = microtime(true) + 8;
            do {
                $ids = [];
                foreach ($processes as $process) {
                    preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $matches);
                    $ids[] = (int) ($matches[1] ?? 0);
                }
                $waiting = DB::table('information_schema.INNODB_TRX')
                    ->whereIn('trx_mysql_thread_id', $ids)->where('trx_state', 'LOCK WAIT')->count() === 2;
                if (! $waiting) {
                    usleep(50000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'Both actions must wait on the same order row.');
            $connection->commit();

            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $this->assertStringContainsString('DONE', $process->getOutput());
            }
            $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
            $this->assertSame(1, $order->events()->where('event_type', 'order_completed')->count());
            $this->assertSame(3, $order->events()->count());
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            $this->assertSame(0, $connection->transactionLevel());
        }
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
