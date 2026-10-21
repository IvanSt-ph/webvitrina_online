<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ChatImageConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function interleavings(): array
    {
        $cases = [];
        foreach ([false, true] as $admin) {
            foreach (['conversation', 'sender', 'recipient'] as $target) {
                foreach ([false, true] as $deleteFirst) {
                    foreach ([false, true] as $rollback) {
                        $cases[($admin ? 'admin' : 'user')." $target ".($deleteFirst ? 'delete first' : 'send first').($rollback ? ' rollback' : ' commit')]
                            = [$admin, $target, $deleteFirst, $rollback];
                    }
                }
            }
        }

        return $cases;
    }

    #[DataProvider('interleavings')]
    public function test_send_and_deletion_preserve_file_references(bool $admin, string $targetKind, bool $deleteFirst, bool $rollback): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => $admin ? 'admin' : 'seller']);
        if ($admin) {
            User::factory()->admin()->create(); // F07: deleting sender must be permitted.
        }
        $sender = $admin ? $seller : $buyer;
        $recipient = $admin ? $buyer : $seller;
        $target = $targetKind === 'sender' ? $sender : $recipient;
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'conversation_type' => $admin ? Conversation::TYPE_SUPPORT : Conversation::TYPE_MARKETPLACE,
        ]);
        Storage::disk('local')->put('chat-images/existing.webp', 'existing');
        $conversation->messages()->create([
            'sender_id' => $sender->id, 'body' => 'existing', 'image_path' => 'chat-images/existing.webp',
        ]);
        $deletion = $targetKind === 'conversation' ? 'conversation' : 'account';
        $actions = $deleteFirst ? [$deletion, 'send'] : ['send', $deletion];
        $processes = $inputs = $connections = [];
        try {
            foreach ($actions as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'private_root' => Storage::disk('local')->path(''),
                    'public_root' => Storage::disk('public')->path(''),
                    'sender_id' => $sender->id, 'target_id' => $target->id,
                    'conversation_id' => $conversation->id, 'action' => $action, 'admin' => $admin,
                    'pause' => $i === 0, 'rollback' => $i === 0 && $rollback,
                    'conflict_table' => $targetKind === 'sender' ? 'users' : 'conversations',
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/chat-image-worker.php')], base_path(), timeout: 40);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'LOCKED' : 'CONFLICT');
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $connections[] = $match[1];
            }
            $this->assertNotSame($connections[0], $connections[1]);
            $this->assertStringNotContainsString('RESULT:', $processes[1]->getOutput());
            $inputs[0]->write("release\n");
            $this->awaitOutput($processes[0], 'TX_DONE');
            $inputs[1]->write("continue\n");
            // InputStream::write only queues bytes. Pump worker 2's pipes until
            // it acknowledges receipt before waiting for worker 1, whose
            // afterCommit notification may need worker 2's recipient User lock.
            $this->awaitOutput($processes[1], 'CONTINUING');
            foreach ($processes as $i => $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput().' '.$process->getOutput());
                $expected = $i === 0 ? ($rollback ? 'rollback' : 'ok') : ($deleteFirst && ! $rollback ? 'deleted' : 'ok');
                $this->assertStringContainsString('RESULT:'.$expected, $process->getOutput());
            }
            $expectedCount = $deleteFirst && $rollback ? 2 : 0;
            $this->assertSame($expectedCount, Message::where('conversation_id', $conversation->id)->count());
            $references = Message::whereNotNull('image_path')->orderBy('image_path')->pluck('image_path')->all();
            $files = Storage::disk('local')->allFiles('chat-images');
            sort($files);
            $this->assertSame($references, $files, 'No missing references or orphan chat files after both sessions complete.');
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
        }
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 10;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000); // Output polling only; NOWAIT 3572 is the contention evidence.
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
