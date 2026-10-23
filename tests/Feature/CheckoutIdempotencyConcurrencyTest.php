<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Tests\Support\CheckoutTestCase;
use Tests\Support\TestDatabaseSafety;

class CheckoutIdempotencyConcurrencyTest extends CheckoutTestCase
{
    public static function outcomes(): array
    {
        return [
            'same key commit' => ['commit', false, 5],
            'same key rollback' => ['rollback', false, 5],
            'PHP dies before commit' => ['crash before commit', false, 5],
            'PHP dies after commit' => ['crash after commit', false, 5],
            'unknown committed' => ['unknown committed', false, 5],
            'unknown rolled back' => ['unknown rolled back', false, 5],
            'different keys sufficient stock' => ['commit', true, 5],
            'different keys limited stock' => ['commit', true, 1],
        ];
    }

    #[DataProvider('outcomes')]
    public function test_real_sessions_serialize_checkout(string $outcome, bool $different, int $stock): void
    {
        [$buyer] = $this->prepareCart();
        $product = Product::sole();
        $product->update(['stock' => $stock]);
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        $inputs = $processes = [];
        $phase = 'starting workers';
        try {
            foreach (['first', 'second'] as $mode) {
                $workerSnapshot = $snapshot;
                if ($different && $mode === 'second') {
                    $workerSnapshot['checkout_token'] = \Illuminate\Support\Str::random(40);
                }
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'buyer' => $buyer->id,
                    'snapshot' => $workerSnapshot, 'mode' => $mode,
                    'outcome' => $outcome, 'different' => $different,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/checkout-idempotency-worker.php')], base_path(), timeout: 40);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $phase = 'waiting for '.$mode.' '.($mode === 'first' ? 'LOCKED' : 'LOCK_CONFLICT');
                $this->awaitSignal($process, $mode === 'first' ? 'LOCKED' : 'LOCK_CONFLICT');
            }
            $this->assertStringNotContainsString('DONE', $processes[1]->getOutput());
            // Do not park a live MySQL transaction on stdin while waiting for
            // another process to exit. First let it resume the actual locking
            // SELECT; the first worker still owns the conflicting lock.
            $inputs[1]->write("continue\n");
            $phase = 'waiting for second CONTINUING';
            $this->awaitSignal($processes[1], 'CONTINUING');
            if ($outcome === 'crash before commit') {
                $processes[0]->stop(0);
            } else {
                $inputs[0]->write("release\n");
                if ($outcome === 'crash after commit') {
                    $phase = 'waiting for first COMMITTED';
                    $this->awaitSignal($processes[0], 'COMMITTED');
                    $processes[0]->stop(0);
                } else {
                    $phase = 'waiting for first exit';
                    $this->assertSame(0, $processes[0]->wait(), 'First worker failed');
                    $signal = str_starts_with($outcome, 'unknown ') ? 'UNKNOWN' : ($outcome === 'rollback' ? 'ROLLED_BACK' : 'DONE');
                    $this->assertStringContainsString($signal, $processes[0]->getOutput());
                }
            }
            $phase = 'waiting for second exit';
            $this->assertSame(0, $processes[1]->wait(), 'Second worker failed');
            $this->assertStringContainsString($different && $stock === 1 ? 'STOCK_REJECTED' : 'DONE', $processes[1]->getOutput());
            $count = $different && $stock > 1 ? 2 : 1;
            foreach (['orders', 'order_items', 'order_events', 'checkout_attempts'] as $table) {
                $this->assertDatabaseCount($table, $count);
            }
            // Site notifications survive even if PHP dies in the first afterCommit
            // callback or never receives the commit acknowledgement.
            $this->assertDatabaseCount('user_notifications', $count);
            foreach (Order::all() as $order) {
                $notification = \App\Models\UserNotification::where('data->order_id', $order->id)->sole();
                $this->assertSame($order->seller_id, $notification->user_id);
                $this->assertSame('order_created', $notification->type);
                $this->assertSame(route('seller.orders.show', $order, false), $notification->url);
            }
            $this->assertSame($stock - $count, $product->fresh()->stock);
            $this->assertSame([$buyer->id], Order::pluck('user_id')->unique()->values()->all());
        } catch (ProcessTimedOutException $exception) {
            $states = [];
            foreach ($processes as $index => $process) {
                // Whitelist protocol lines: never print SQL, session data, database
                // configuration or arbitrary PHP error output containing bindings.
                preg_match_all('/^(?:CONNECTION:\d+|BOOTED|LOCKED|LOCK_CONFLICT|CONTINUING|RELEASE_RECEIVED|ROLLBACK_REQUESTED|COMMITTED|DONE|STOCK_REJECTED|UNKNOWN|ROLLED_BACK|CLEANUP|CLOSED)\r?$/m',
                    $process->getOutput(), $matches);
                $states[$index === 0 ? 'first' : 'second'] = [
                    'running' => $process->isRunning(), 'exit_code' => $process->getExitCode(),
                    'signals' => $matches[0],
                ];
            }
            throw new \RuntimeException('Checkout worker timeout; phase='.$phase.'; outcome='.$outcome
                .'; states='.json_encode($states, JSON_THROW_ON_ERROR), 0, $exception);
        } finally {
            foreach ($inputs as $input) { $input->close(); }
            foreach ($processes as $process) { $process->stop(0); }
            $probe = app('db.factory')->make(config('database.connections.mysql'), 'mysql');
            TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $probe);
            $probe->getPdo();
            try {
                TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $probe);
                $probe->beginTransaction();
                $probe->select('SELECT id FROM products WHERE id = ? FOR UPDATE NOWAIT', [$product->id]);
                $probe->select('SELECT token_hash FROM checkout_attempts FOR UPDATE NOWAIT');
                $probe->select('SELECT id FROM orders FOR UPDATE NOWAIT');
            } finally {
                if ($probe->transactionLevel() > 0) { $probe->rollBack(); }
                $probe->disconnect();
            }
        }
    }

    private function awaitSignal(Process $process, string $signal): void
    {
        // waitUntil() first calls updateStatus(), which can consume a signal
        // before its temporary callback is installed. Always inspect the entire
        // retained output, including bytes read by earlier status checks.
        while (! str_contains($process->getOutput(), $signal) && $process->isRunning()) {
            $process->checkTimeout();
            // Polling cadence only: progress requires the explicit worker signal,
            // and the lock conflict itself is proved by MySQL NOWAIT error 3572.
            usleep(20000);
        }
        $this->assertStringContainsString($signal, $process->getOutput(), 'Worker did not reach the expected synchronization point');
    }
}
