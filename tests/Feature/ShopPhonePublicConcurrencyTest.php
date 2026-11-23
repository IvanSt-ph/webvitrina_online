<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ShopPhonePublicConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Independent sessions need committed fixtures. Rebuild the test schema next time.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function orders(): array
    {
        return [['publish', 'change'], ['change', 'publish']];
    }

    #[DataProvider('orders')]
    public function test_phone_change_and_publication_serialize_on_the_owner(string $first, string $second): void
    {
        $owner = User::factory()->seller()->create(['phone' => '+37377111222', 'phone_verified_at' => now()]);
        $shop = $owner->shop()->create(['name' => 'Concurrent phone']);
        $processes = $inputs = [];
        try {
            foreach ([$first, $second] as $index => $action) {
                $input = new InputStream();
                $input->write(json_encode(config('database'), JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/shop-phone-visibility-worker.php'),
                    (string) $owner->id, (string) $shop->id, $action, $index === 0 ? 'holder' : 'contender'],
                    base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $index === 0 ? 'LOCKED' : 'LOCK_CONFLICT');
            }
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:success', $processes[0]->getOutput());
            $inputs[1]->write("continue\n");
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertStringContainsString($second === 'publish' ? 'RESULT:rejected' : 'RESULT:success', $processes[1]->getOutput());
            $this->assertFalse($shop->fresh()->phone_public);
            $this->assertNull($shop->public_phone);
            $this->assertNull($owner->fresh()->phone_verified_at);
            $this->assertSame('+37377555666', $owner->fresh()->phone);
        } finally {
            \Tests\Support\WorkerProcessCleanup::stop($processes, $inputs);
            \Tests\Support\ConcurrencyLockProbe::assertReleased(['users' => [$owner->id], 'shops' => [$shop->id]], $processes);
        }
    }

    private function awaitOutput(Process $process, string $expected): void
    {
        $deadline = microtime(true) + 10;
        while (! str_contains($process->getOutput(), $expected) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($expected, $process->getOutput(), $process->getErrorOutput());
    }
}
