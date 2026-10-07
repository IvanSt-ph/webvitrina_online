<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductCrudRepository;
use App\Services\ImageService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProductImageLifecycleTest extends TestCase
{
    use DatabaseTruncation;

    public static function tearDownAfterClass(): void
    {
        // Following RefreshDatabase tests must rebuild their migration seed data.
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // No test transaction wrapper: callbacks must follow actual MySQL commits.
        $this->beforeApplicationDestroyed(function () {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_replacement_waits_for_outer_commit_and_cleans_both_derivatives(): void
    {
        $product = $this->product();
        DB::beginTransaction();
        $updated = app(ProductService::class)->update($product, [], $this->upload());
        $this->assertPairExists($product->image);
        $this->assertPairExists($updated->image);
        DB::commit();
        $this->assertPairMissing($product->image);
        $this->assertPairExists($product->fresh()->image);
    }

    public function test_outer_rollback_cleans_new_files_after_successful_inner_savepoint(): void
    {
        $product = $this->product();
        $before = $product->fresh()->getAttributes();
        DB::beginTransaction();
        $updated = app(ProductService::class)->update($product, ['price' => 999], $this->upload(), [$this->upload()]);
        $newPaths = [$updated->image, ...$updated->gallery];
        DB::rollBack();
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertPairExists($product->image);
        foreach ($newPaths as $path) {
            $this->assertPairMissing($path);
        }
    }

    public function test_rollback_of_intermediate_savepoint_keeps_successful_sibling_upload(): void
    {
        $product = $this->product();
        DB::beginTransaction();
        $first = app(ProductService::class)->update($product, [], $this->upload());
        DB::beginTransaction();
        $second = app(ProductService::class)->update($product, [], $this->upload());
        DB::rollBack();
        $this->assertSame($first->image, $product->fresh()->image);
        $this->assertPairExists($first->image);
        $this->assertPairMissing($second->image);
        DB::commit();
        $this->assertPairExists($first->image);
        $this->assertPairMissing($product->image);
    }

    public function test_exception_after_upload_and_scheduled_deletion_preserves_old_state(): void
    {
        $product = $this->product();
        $before = Storage::disk('public')->allFiles();
        $this->mock(ProductCrudRepository::class)->shouldReceive('update')->once()
            ->andThrow(new \RuntimeException('injected database failure'));
        $this->fails(fn () => app(ProductService::class)->update($product, [], $this->upload()));
        $this->assertSame($product->image, $product->fresh()->image);
        $this->assertSame($before, Storage::disk('public')->allFiles());
    }

    public function test_create_failure_cleans_uploaded_main_image(): void
    {
        $this->mock(ProductCrudRepository::class)->shouldReceive('create')->once()
            ->andThrow(new \RuntimeException('injected create failure'));
        $this->fails(fn () => app(ProductService::class)->create(['title' => 'Failed'], $this->upload()));
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseMissing('products', ['title' => 'Failed']);
    }

    public function test_gallery_batch_failure_cleans_partial_uploads_and_preserves_existing_files(): void
    {
        $product = $this->product();
        $gallery = $this->seedPair('products/gallery/medium/existing.webp');
        $product->update(['gallery' => [$gallery]]);
        $before = Storage::disk('public')->allFiles();
        try {
            app(ProductService::class)->update($product, [], $this->upload(), [
                $this->upload(), UploadedFile::fake()->create('bad.jpg', 1, 'image/jpeg'),
            ], [$gallery]);
            $this->fail('Invalid second gallery image must fail.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('JPG', $exception->getMessage());
        }
        $this->assertSame($product->image, $product->fresh()->image);
        $this->assertSame([$gallery], $product->fresh()->gallery);
        $this->assertSame($before, Storage::disk('public')->allFiles());
    }

    public function test_late_attribute_failure_cleans_successfully_written_main_and_gallery(): void
    {
        $product = $this->product();
        $before = Storage::disk('public')->allFiles();
        $this->mock(\App\Services\AttributeService::class)->shouldReceive('sync')->once()
            ->andThrow(new \RuntimeException('injected attribute failure'));
        $this->fails(fn () => app(ProductService::class)->update($product, [], $this->upload(),
            [$this->upload(), $this->upload()], [], ['test' => 'value']));
        $this->assertSame($before, Storage::disk('public')->allFiles());
        $this->assertSame([], $product->fresh()->gallery);
        $this->assertSame($product->image, $product->fresh()->image);
    }

    public function test_partial_derivative_write_failure_cleans_medium_and_preserves_old_image(): void
    {
        $product = $this->product();
        $before = Storage::disk('public')->allFiles();
        $proxy = \Mockery::mock(Storage::disk('public'))->makePartial();
        $proxy->shouldReceive('put')->with(\Mockery::on(fn ($path) => str_contains($path, '/thumb/')), \Mockery::any())
            ->once()->andReturn(false);
        Storage::set('public', $proxy);
        try {
            app(ProductService::class)->update($product, [], $this->upload());
            $this->fail('A failed derivative write must abort the update.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('сохранить', $exception->getMessage());
        }
        $this->assertSame($before, Storage::disk('public')->allFiles());
        $this->assertSame($product->image, $product->fresh()->image);
    }

    public function test_successful_create_is_compensated_when_outer_transaction_rolls_back(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        DB::beginTransaction();
        $product = app(ProductService::class)->create([
            'user_id' => $seller->id, 'title' => 'Rolled back create', 'price' => 100,
        ], $this->upload(), [$this->upload(), $this->upload()]);
        $this->assertCount(6, Storage::disk('public')->allFiles());
        DB::rollBack();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_gallery_deletion_rolls_back_then_commits_consistently(): void
    {
        $product = $this->product();
        $path = $this->seedPair('products/gallery/medium/gallery.webp');
        $product->update(['gallery' => [$path]]);
        DB::beginTransaction();
        app(ProductService::class)->deleteFromGallery($product, $path);
        $this->assertSame([], $product->fresh()->gallery);
        $this->assertPairExists($path);
        DB::rollBack();
        $this->assertSame([$path], $product->fresh()->gallery);
        $this->assertPairExists($path);
        app(ProductService::class)->deleteFromGallery($product, $path);
        $this->assertSame([], $product->fresh()->gallery);
        $this->assertPairMissing($path);
    }

    public function test_soft_delete_rolls_back_then_removes_files_only_on_commit(): void
    {
        $product = $this->product();
        $path = $this->seedPair('products/gallery/medium/gallery.webp');
        $product->update(['gallery' => [$path]]);
        DB::beginTransaction();
        app(ProductService::class)->delete($product);
        $this->assertSoftDeleted($product);
        $this->assertPairExists($product->image);
        $this->assertPairExists($path);
        DB::rollBack();
        $this->assertNotNull($product->fresh());
        $this->assertPairExists($product->image);
        app(ProductService::class)->delete($product);
        $this->assertSoftDeleted($product);
        $this->assertPairMissing($product->image);
        $this->assertPairMissing($path);
    }

    public function test_placeholder_and_shared_images_survive(): void
    {
        foreach (['default/no-image.png', 'products/placeholder.png', 'products/default-product.png'] as $path) {
            $product = $this->product($path);
            app(ProductService::class)->update($product, [], $this->upload());
            $this->assertPairExists($path);
        }
        $product = $this->product();
        $other = $this->product($product->image);
        app(ProductService::class)->delete($product);
        $this->assertPairExists($other->image);
        app(ProductService::class)->delete($other);
        $this->assertPairMissing($other->image);
    }

    public function test_main_image_shared_with_own_gallery_is_kept_until_last_reference_removed(): void
    {
        $product = $this->product();
        $product->update(['gallery' => [$product->image]]);
        app(ProductService::class)->update($product, [], $this->upload());
        $this->assertPairExists($product->image);
        app(ProductService::class)->deleteFromGallery($product, $product->image);
        $this->assertPairMissing($product->image);
    }

    public function test_stale_models_do_not_lose_gallery_updates_or_leak_replaced_images(): void
    {
        $product = $this->product();
        $stale = $product->fresh();
        $first = app(ProductService::class)->update($product, [], $this->upload(), [$this->upload()]);
        $second = app(ProductService::class)->update($stale, [], $this->upload(), [$this->upload()]);
        $this->assertCount(2, $second->gallery);
        $this->assertPairMissing($first->image);
        $this->assertPairExists($second->image);
        foreach ($second->gallery as $path) {
            $this->assertPairExists($path);
        }
    }

    public function test_overlapping_updates_wait_for_row_lock_and_preserve_only_committed_images(): void
    {
        $product = $this->product();
        $processes = [];
        $inputs = [];
        try {
            foreach (['first', 'second'] as $mode) {
                $input = new InputStream();
                $input->write(json_encode(['database' => config('database')], JSON_THROW_ON_ERROR) . "\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/product-image-worker.php'),
                    (string) $product->id, $mode], base_path(), timeout: 25);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                // ATTEMPT is emitted before ProductService enters its transaction.
                // Wait for the database hook so lock observation starts only when
                // the second worker is dispatching the actual SELECT ... FOR UPDATE.
                $expected = $mode === 'first' ? 'LOCKED' : 'LOCK_QUERY';
                $deadline = microtime(true) + 10;
                while (! str_contains($process->getOutput(), $expected) && $process->isRunning() && microtime(true) < $deadline) {
                    usleep(20000);
                }
                $this->assertStringContainsString($expected, $process->getOutput(), $process->getErrorOutput());
            }
            preg_match('/CONNECTION:(\d+)/', $processes[1]->getOutput(), $matches);
            $this->assertNotEmpty($matches);
            $deadline = microtime(true) + 8;
            do {
                $waiting = DB::table('information_schema.INNODB_TRX')
                    ->where('trx_mysql_thread_id', $matches[1])->where('trx_state', 'LOCK WAIT')->exists();
                if (! $waiting) {
                    usleep(50000);
                }
            } while (! $waiting && microtime(true) < $deadline);
            $this->assertTrue($waiting, 'Second update must actually wait on the product row lock. ' .
                implode("\n", array_map(fn ($process) => $process->getOutput() . $process->getErrorOutput(), $processes)));
            $this->assertStringNotContainsString('DONE', $processes[1]->getOutput());
            $this->assertPairExists($product->image);
            $inputs[0]->write("release\n");
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
            }
            $updated = $product->fresh();
            $this->assertCount(2, $updated->gallery);
            foreach ([$updated->image, ...$updated->gallery] as $path) {
                $this->assertPairExists($path);
            }
            $this->assertPairMissing($product->image);
            $this->assertCount(6, Storage::disk('public')->allFiles());
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
                $this->assertFalse($process->isRunning());
            }
        }
    }

    public function test_legacy_original_and_thumb_are_removed_together(): void
    {
        $product = $this->product('products/legacy.jpg');
        app(ProductService::class)->update($product, [], $this->upload());
        $this->assertPairMissing('products/legacy.jpg');
        $this->assertPairExists($product->fresh()->image);
    }

    public function test_purge_rolls_back_safely_and_command_cleans_legacy_files(): void
    {
        $product = $this->product('products/legacy.jpg');
        $product->delete();
        $product->forceFill(['deleted_at' => now()->subDays(100)])->save();
        DB::beginTransaction();
        $this->assertTrue(app(ProductService::class)->purge($product, now()->subDays(90)));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertPairExists($product->image);
        DB::rollBack();
        $this->assertSoftDeleted($product);
        $this->assertPairExists($product->image);
        $recent = $this->product('products/recent.jpg');
        $recent->delete();
        $this->artisan('products:purge-old')->assertSuccessful();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertPairMissing($product->image);
        $this->assertSoftDeleted($recent);
        $this->assertPairExists($recent->image);
    }

    public function test_filesystem_failure_after_commit_is_logged_without_failing_update(): void
    {
        $product = $this->product();
        Log::spy();
        $disk = Storage::disk('public');
        $proxy = \Mockery::mock($disk)->makePartial();
        $proxy->shouldReceive('delete')->with($product->image)->once()->andReturn(false);
        Storage::set('public', $proxy);
        $updated = app(ProductService::class)->update($product, ['title' => 'Committed'], $this->upload());
        $this->assertSame('Committed', $product->fresh()->title);
        $this->assertSame($updated->image, $product->fresh()->image);
        $this->assertPairExists($updated->image);
        $disk->assertExists($product->image);
        $disk->assertMissing(ImageService::thumbPath($product->image));
        Log::shouldHaveReceived('error')->with('Product image cleanup failed; retry required',
            \Mockery::on(fn ($context) => $context['path'] === $product->image && $context['phase'] === 'after_commit'))->once();
    }

    public function test_admin_and_seller_endpoints_share_safe_create_update_gallery_and_delete(): void
    {
        foreach (['admin', 'seller'] as $role) {
            $seller = User::factory()->create(['role' => 'seller']);
            $actor = $role === 'seller' ? $seller : User::factory()->create(['role' => 'admin']);
            $country = Country::create(['name' => 'Moldova', 'currency' => 'MDL']);
            $city = City::create(['country_id' => $country->id, 'name' => 'Chisinau']);
            $payload = [
                'user_id' => $seller->id, 'title' => 'Lifecycle ' . $role, 'price' => 100, 'stock' => 5,
                'category_id' => Category::factory()->create()->id, 'city_id' => $city->id,
                'country_id' => $country->id, 'description' => 'Product description', 'status' => 'draft',
                'image' => $this->upload(), 'gallery' => [$this->upload()],
            ];
            $this->actingAs($actor)->post(route($role . '.products.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $product = Product::where('user_id', $seller->id)->sole();
            $old = $product->image;
            $this->assertPairExists($old);
            $payload['image'] = $this->upload();
            unset($payload['gallery']);
            $this->patch(route($role . '.products.update', $product), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $this->assertPairMissing($old);
            $product->refresh();
            $this->assertPairExists($product->image);
            $gallery = $product->gallery[0];
            $this->deleteJson(route($role . '.products.gallery.delete', $product), ['path' => $gallery])->assertOk();
            $this->assertPairMissing($gallery);
            $this->assertSame([], $product->fresh()->gallery);
            $this->delete(route($role . '.products.destroy', $product))->assertRedirect();
            $this->assertSoftDeleted($product);
            $this->assertPairMissing($product->image);
        }
    }

    private function product(string $image = 'products/medium/old.webp'): Product
    {
        return Product::create([
            'user_id' => User::factory()->create(['role' => 'seller'])->id,
            'title' => 'Lifecycle ' . uniqid(), 'price' => 100, 'stock' => 5,
            'image' => $this->seedPair($image), 'gallery' => [], 'status' => 'draft',
        ]);
    }

    private function seedPair(string $path): string
    {
        Storage::disk('public')->put($path, 'image');
        Storage::disk('public')->put(ImageService::thumbPath($path), 'thumb');
        return $path;
    }

    private function upload(): UploadedFile
    {
        return UploadedFile::fake()->image('image.jpg', 24, 24);
    }

    private function assertPairExists(string $path): void
    {
        Storage::disk('public')->assertExists([$path, ImageService::thumbPath($path)]);
    }

    private function assertPairMissing(string $path): void
    {
        Storage::disk('public')->assertMissing([$path, ImageService::thumbPath($path)]);
    }

    private function fails(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected injected failure.');
        } catch (\RuntimeException $exception) {
            $this->assertStringStartsWith('injected', $exception->getMessage());
        }
    }
}
