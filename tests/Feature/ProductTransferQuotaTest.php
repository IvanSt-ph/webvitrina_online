<?php

namespace Tests\Feature;

use App\Models\{Product, User};
use App\Services\{ImageService, ProductService, SellerPlanService};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Support\ProductQuotaFixtures;
use Tests\TestCase;

class ProductTransferQuotaTest extends TestCase
{
    use RefreshDatabase, ProductQuotaFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep the real write barrier on a test-owned inode until transaction cleanup finishes.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'logging.default' => 'null',
            'queue.default' => 'sync', 'mail.default' => 'array',
        ]);
    }

    public function test_admin_transfer_to_a_full_seller_preserves_the_product_and_error_contract(): void
    {
        [$source] = $this->quotaFixture(1);
        [$recipient] = $this->quotaFixture(10);
        $product = Product::where('user_id', $source->id)->firstOrFail();
        $before = $product->getAttributes();
        $this->actingAs(User::factory()->admin()->create())
            ->patchJson(route('admin.products.update', $product), ['user_id' => $recipient->id, 'title' => 'Not saved'])
            ->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame(10, Product::where('user_id', $recipient->id)->count());
    }

    public function test_direct_service_refuses_before_upload_or_other_changes(): void
    {
        [$source] = $this->quotaFixture(1);
        [$recipient] = $this->quotaFixture(10);
        $product = Product::where('user_id', $source->id)->firstOrFail();
        $before = $product->getAttributes();
        $images = $this->mock(ImageService::class);
        $images->shouldNotReceive('upload');
        $images->shouldNotReceive('uploadGallery');
        try {
            app(ProductService::class)->update($product, ['user_id' => $recipient->id, 'slug' => 'not-saved'],
                UploadedFile::fake()->image('not-uploaded.jpg'));
            $this->fail('A transfer must enforce the recipient quota.');
        } catch (ValidationException $exception) {
            $this->assertSame(['user_id' => [app(SellerPlanService::class)->limitMessage($recipient)]], $exception->errors());
        }
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertDatabaseMissing('product_slugs', ['product_id' => $product->id]);
    }

    public function test_transfer_uses_the_last_slot_and_revokes_the_previous_owners_mutation_rights(): void
    {
        [$source] = $this->quotaFixture(1);
        [$recipient] = $this->quotaFixture(9);
        $product = Product::where('user_id', $source->id)->firstOrFail();
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.products.update', $product), ['user_id' => $recipient->id])
            ->assertRedirect(route('admin.products.index'));
        $this->assertSame($recipient->id, $product->fresh()->user_id);
        $this->assertSame(10, Product::where('user_id', $recipient->id)->count());
        $this->assertSame(0, Product::where('user_id', $source->id)->count());
        $this->expectException(AuthorizationException::class);
        app(ProductService::class)->update($product, ['title' => 'Old owner edit'], actor: $source);
    }

    public function test_same_owner_edit_at_capacity_does_not_consume_another_slot(): void
    {
        [$seller] = $this->quotaFixture(10);
        $product = Product::where('user_id', $seller->id)->firstOrFail();
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.products.update', $product), ['user_id' => $seller->id, 'title' => 'Edited'])
            ->assertRedirect(route('admin.products.index'));
        $this->assertSame('Edited', $product->fresh()->title);
        $this->assertSame(10, Product::where('user_id', $seller->id)->count());
    }

    public function test_stale_route_model_does_not_turn_an_already_completed_transfer_into_an_extra_slot(): void
    {
        [$source] = $this->quotaFixture(1);
        [$recipient] = $this->quotaFixture(9);
        $stale = Product::where('user_id', $source->id)->firstOrFail();
        app(ProductService::class)->update($stale, ['user_id' => $recipient->id]);
        $result = app(ProductService::class)->update($stale, ['user_id' => $recipient->id, 'title' => 'Current owner edit']);
        $this->assertSame('Current owner edit', $result->title);
        $this->assertSame(10, Product::where('user_id', $recipient->id)->count());
    }
}
