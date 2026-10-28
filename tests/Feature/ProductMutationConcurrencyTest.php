<?php

namespace Tests\Feature;

use App\Models\{Category, City, Country, Product, User};
use App\Services\ImageService;
use Illuminate\Foundation\Testing\{RefreshDatabase, RefreshDatabaseState};
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\ConcurrencyLockProbe;
use Tests\TestCase;

class ProductMutationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Independent connections require committed fixtures in the guarded test DB.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function races(): array
    {
        $cases = [];
        foreach (['block', 'reassign'] as $change) {
            foreach (['edit', 'main', 'gallery-add', 'gallery-update-delete', 'gallery-delete'] as $action) {
                foreach (['admin', 'seller'] as $first) {
                    $cases[$change.' '.$first.' first '.$action] = [$change, $action, $first];
                }
            }
        }
        $cases['reassignment before deletion'] = ['reassign', 'delete', 'admin'];

        return $cases;
    }

    #[DataProvider('races')]
    public function test_product_lock_serializes_moderation_and_seller_mutations(string $change, string $action, string $first): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $newOwner = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();
        $country = Country::create(['name' => 'Race country', 'currency' => 'MDL']);
        $city = City::create(['name' => 'Race city', 'country_id' => $country->id]);
        $main = 'products/medium/original.webp';
        $gallery = 'products/gallery/medium/original.webp';
        foreach ([$main, $gallery] as $path) {
            Storage::disk('public')->put($path, 'original');
            Storage::disk('public')->put(ImageService::thumbPath($path), 'thumbnail');
        }
        $product = Product::create([
            'user_id' => $seller->id, 'title' => 'Original product', 'price' => 100, 'stock' => 5,
            'category_id' => Category::factory()->create()->id, 'city_id' => $city->id,
            'currency_base' => 'MDL', 'status' => Product::STATUS_ACTIVE, 'image' => $main, 'gallery' => [$gallery],
        ]);
        $originalFiles = Storage::disk('public')->allFiles();
        $processes = $inputs = [];
        try {
            foreach ([$first, $first === 'admin' ? 'seller' : 'admin'] as $i => $actor) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''),
                    'seller' => $seller->id, 'new_owner' => $newOwner->id, 'admin' => $admin->id,
                    'product' => $product->id, 'change' => $change, 'action' => $action,
                    'actor' => $actor, 'first' => $i === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/product-mutation-worker.php')], base_path(), timeout: 30);
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
            $rejected = $change === 'reassign' && $first === 'admin';
            $this->assertStringContainsString('RESULT:'.($rejected ? 'forbidden' : 'ok'), $processes[1]->getOutput());
            $current = $product->fresh();
            $this->assertNotNull($current);
            $this->assertSame($change === 'block' ? Product::STATUS_BLOCKED : Product::STATUS_ACTIVE, $current->status);
            $this->assertSame($change === 'reassign' ? $newOwner->id : $seller->id, $current->user_id);
            if ($rejected) {
                $this->assertSame('Original product', $current->title);
                $this->assertSame($main, $current->image);
                $this->assertSame([$gallery], $current->gallery);
                $this->assertSame($originalFiles, Storage::disk('public')->allFiles());
                $this->assertDatabaseMissing('product_slugs', ['product_id' => $product->id]);
            } else {
                $this->assertSame($action === 'gallery-delete' ? 'Original product' : 'Seller correction', $current->title);
                if ($action === 'main') {
                    $this->assertNotSame($main, $current->image);
                    Storage::disk('public')->assertExists([$current->image, ImageService::thumbPath($current->image)]);
                    Storage::disk('public')->assertMissing([$main, ImageService::thumbPath($main)]);
                } elseif ($action === 'gallery-add') {
                    $this->assertCount(2, $current->gallery);
                    Storage::disk('public')->assertExists($current->gallery);
                } elseif (in_array($action, ['gallery-delete', 'gallery-update-delete'], true)) {
                    $this->assertSame([], $current->gallery);
                    Storage::disk('public')->assertMissing([$gallery, ImageService::thumbPath($gallery)]);
                }
            }
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            ConcurrencyLockProbe::assertReleased(['products' => [$product->id]], $processes);
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
