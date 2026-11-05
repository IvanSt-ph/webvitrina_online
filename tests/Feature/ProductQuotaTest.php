<?php

namespace Tests\Feature;

use App\Models\{Product, User};
use App\Repositories\ProductCrudRepository;
use App\Services\{ImageService, ProductService, SellerPlanService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ProductQuotaFixtures;
use Tests\Support\TestFileSafety;
use Tests\TestCase;

class ProductQuotaTest extends TestCase
{
    use RefreshDatabase, ProductQuotaFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        // Limiters may already be resolved by providers; fail closed on shared cache.
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));

        // Retain the real write barrier on a test-owned inode through rollback.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync',
            'mail.default' => 'array', 'logging.default' => 'null',
        ]);
    }

    public static function plans(): array
    {
        return [['starter', 10], ['basic', 25], ['pro', 50], ['business', 100], ['unknown', 10]];
    }

    #[DataProvider('plans')]
    public function test_last_slot_is_available_and_all_non_deleted_statuses_count(string $plan, int $limit): void
    {
        [$seller, $data] = $this->quotaFixture($limit - 1, $plan);
        app(ProductService::class)->create($data);
        $this->assertSame($limit, Product::where('user_id', $seller->id)->count());
        try {
            app(ProductService::class)->create(array_merge($data, ['title' => 'Over quota']));
            $this->fail('Creation must reject an exhausted quota.');
        } catch (ValidationException $exception) {
            $this->assertSame(['user_id' => [app(SellerPlanService::class)->limitMessage($seller)]], $exception->errors());
        }
        $this->assertDatabaseMissing('products', ['title' => 'Over quota']);
    }

    public function test_enterprise_remains_unlimited(): void
    {
        [$seller, $data] = $this->quotaFixture(101, 'enterprise');
        app(ProductService::class)->create($data);
        $this->assertSame(102, Product::where('user_id', $seller->id)->count());
    }

    public function test_exhaustion_keeps_seller_and_admin_response_contracts(): void
    {
        [$seller, $data] = $this->quotaFixture(10);
        $this->actingAs($seller)->from(route('seller.products.create'))
            ->post(route('seller.products.store'), $data)->assertRedirect(route('seller.products.create'))
            ->assertSessionHasErrors('product_limit')->assertSessionHasInput('title', $data['title']);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson(route('admin.products.store'), $data)
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->assertSame(10, Product::where('user_id', $seller->id)->count());
    }

    public function test_authoritative_refusal_has_no_file_or_database_side_effects(): void
    {
        $this->fakePublicStorage();
        [$seller, $data] = $this->quotaFixture(10);
        $before = Product::where('user_id', $seller->id)->pluck('id')->all();
        $images = $this->mock(ImageService::class);
        $images->shouldNotReceive('upload');
        $images->shouldNotReceive('uploadGallery');
        $this->mock(ProductCrudRepository::class)->shouldNotReceive('create');
        $attributesBefore = \Illuminate\Support\Facades\DB::table('attribute_values')->count();
        $slugsBefore = \Illuminate\Support\Facades\DB::table('product_slugs')->count();
        try {
            app(ProductService::class)->create($data, UploadedFile::fake()->image('main.jpg'),
                [UploadedFile::fake()->image('gallery.jpg')], [1 => 'must not be saved']);
            $this->fail('Quota must be checked before side effects.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user_id', $exception->errors());
        }
        $this->assertSame($before, Product::where('user_id', $seller->id)->pluck('id')->all());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('attribute_values', $attributesBefore);
        $this->assertDatabaseCount('product_slugs', $slugsBefore);
    }

    public function test_soft_delete_frees_one_slot_for_recreation(): void
    {
        $this->fakePublicStorage();
        [$seller, $data] = $this->quotaFixture(10);
        $product = Product::where('user_id', $seller->id)->firstOrFail();
        app(ProductService::class)->delete($product, actor: $seller);
        $this->assertSoftDeleted($product);
        app(ProductService::class)->create($data);
        $this->assertSame(10, Product::where('user_id', $seller->id)->count());
        $this->assertSame(11, Product::withTrashed()->where('user_id', $seller->id)->count());
    }

    public function test_creation_uses_the_current_plan_instead_of_a_preflight_user_model(): void
    {
        [$seller, $data] = $this->quotaFixture(10, 'basic');
        $this->assertTrue(app(SellerPlanService::class)->canCreateProduct($seller));
        User::whereKey($seller->id)->update(['seller_plan' => 'starter']);
        $this->expectException(ValidationException::class);
        app(ProductService::class)->create($data);
    }

    private function fakePublicStorage(): void
    {
        // fake() cleans its derived root before applying configuration overrides.
        // Use a fresh disk name, validate that exact root, then alias its adapter.
        $this->assertFalse(ParallelTesting::token(), 'Run existing-schema targets sequentially without a parallel token.');
        $disk = 'group16-quota-'.bin2hex(random_bytes(16));
        $root = storage_path('framework/testing/disks/'.$disk);
        TestFileSafety::assertDirectory($root, mustExist: false);
        $this->assertFalse(file_exists($root) || is_link($root));

        Storage::set('public', Storage::fake($disk));
        TestFileSafety::assertDirectory($root);
        $this->beforeApplicationDestroyed(function () use ($root): void {
            // Neither scenario uploads files. Retain unexpected contents on failure;
            // never recursively clean a shared, redirected or non-empty directory.
            TestFileSafety::assertDirectory($root);
            $this->assertSame(['.', '..'], scandir($root));
            $this->assertTrue(rmdir($root));
        });
    }
}
