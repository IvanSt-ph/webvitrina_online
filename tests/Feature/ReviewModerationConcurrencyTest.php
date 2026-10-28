<?php

namespace Tests\Feature;

use App\Models\Review;
use Illuminate\Foundation\Testing\{RefreshDatabase, RefreshDatabaseState};
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\{ConcurrencyLockProbe, ReviewFixtures};
use Tests\TestCase;

class ReviewModerationConcurrencyTest extends TestCase
{
    use RefreshDatabase, ReviewFixtures;

    public function beginDatabaseTransaction(): void
    {
        // Workers need committed fixtures; the shared bootstrap guards the test database.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function races(): array
    {
        $cases = [];
        foreach (['body', 'rating', 'images'] as $change) {
            foreach ([false, true] as $bulk) {
                foreach (['buyer', 'admin'] as $first) {
                    $cases[$change.' '.($bulk ? 'bulk' : 'single').' '.$first.' first'] = [$change, $bulk, $first];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('races')]
    public function test_content_changes_and_approval_serialize_on_the_review(string $change, bool $bulk, string $first): void
    {
        Storage::fake('public');
        Storage::fake('review_images');
        [$buyer, $admin, , $review] = $this->reviewFixture();
        Storage::disk('review_images')->put('reviews/medium/original.webp', 'old');
        $inputs = $processes = [];
        try {
            foreach ([$first, $first === 'buyer' ? 'admin' : 'buyer'] as $i => $actor) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''),
                    'review_root' => Storage::disk('review_images')->path(''),
                    'buyer' => $buyer->id, 'admin' => $admin->id, 'review' => $review->id,
                    'fingerprint' => $review->moderationFingerprint(),
                    'change' => $change, 'bulk' => $bulk, 'actor' => $actor, 'first' => $i === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/review-moderation-worker.php')], base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'LOCKED' : 'LOCK_CONFLICT');
            }
            $connections = [];
            foreach ($processes as $process) {
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $connections[] = $match[1];
                $this->assertTrue($process->isRunning());
                $this->assertStringNotContainsString('RESULT:', $process->getOutput());
            }
            $this->assertNotSame($connections[0], $connections[1]);
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:ok', $processes[0]->getOutput());
            $inputs[1]->write("continue\n");
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertStringContainsString('RESULT:'.($first === 'buyer' ? 'stale' : 'ok'), $processes[1]->getOutput());
            $current = $review->fresh();
            $this->assertSame(Review::STATUS_PENDING, $current->status);
            $this->assertNull($current->moderated_by);
            $this->assertSame($change === 'body' ? 'Changed text' : 'Inspected text', $current->body);
            $this->assertSame($change === 'rating' ? 5 : 2, (int) $current->rating);
            if ($change === 'images') {
                $this->assertCount(1, $current->images);
                $this->assertNotSame('reviews/medium/original.webp', $current->images->first()->path);
                Storage::disk('review_images')->assertExists($current->images->first()->path);
                Storage::disk('review_images')->assertMissing('reviews/medium/original.webp');
            }
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            ConcurrencyLockProbe::assertReleased(['reviews' => [$review->id]], $processes);
        }
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
