<?php

namespace Tests\Feature;

use App\Models\{Category, City, Country, Product, ProductReport, User};
use App\Services\{ImageService, ProductService};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SellerProductMutation;
use Tests\TestCase;

class ProductMutationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public static function stockEditors(): array
    {
        return [['seller'], ['admin']];
    }

    #[DataProvider('stockEditors')]
    public function test_stock_description_save_after_checkout_preserves_sold_out_stock(string $role): void
    {
        [$actor, $product, $payload] = $this->stockEditFixture($role);
        $this->actingAs($actor)->get(route($role.'.products.edit', $product))
            ->assertOk()->assertSee('name="original_stock" value="5"', false);
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post(route('checkout.quick', $product), ['qty' => 5])
            ->assertRedirect(route('checkout.confirm'))->assertSessionHasNoErrors();
        $checkoutResponse = $this->post(route('checkout.create'), [
            'checkout_token' => session('checkout_token'),
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, (int) $product->fresh()->stock);
        $order = \App\Models\Order::where('user_id', $buyer->id)->sole();
        $checkoutResponse->assertRedirect(route('orders.show', $order));
        $orderBefore = $order->getAttributes();
        $itemsBefore = $order->items()->get()->map->getAttributes()->all();

        $this->actingAs($actor)->patch(route($role.'.products.update', $product), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame('Updated description', $product->fresh()->description);
        $this->assertSame($orderBefore, $order->fresh()->getAttributes());
        $this->assertSame($itemsBefore, $order->items()->get()->map->getAttributes()->all());
    }

    #[DataProvider('stockEditors')]
    public function test_stock_current_manual_change_is_saved(string $role): void
    {
        [$actor, $product, $payload] = $this->stockEditFixture($role);
        $payload['stock'] = 0;
        $this->actingAs($actor)->patch(route($role.'.products.update', $product), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, (int) $product->fresh()->stock);
        $this->assertSame('Updated description', $product->fresh()->description);

        // Manual replenishment from zero remains supported too.
        $payload['original_stock'] = '0';
        $payload['stock'] = 8;
        $this->patch(route($role.'.products.update', $product), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(8, (int) $product->fresh()->stock);
    }

    #[DataProvider('stockEditors')]
    public function test_stock_stale_manual_change_rejects_the_entire_edit(string $role): void
    {
        [$actor, $product, $payload] = $this->stockEditFixture($role);
        $product->decrement('stock');
        $before = $product->fresh()->getAttributes();
        $files = Storage::disk('public')->allFiles();
        $payload['stock'] = 8;
        $payload['title'] = 'Must not be saved';
        $payload['image'] = \Illuminate\Http\UploadedFile::fake()->image('replacement.jpg', 24, 24);
        $images = $this->mock(ImageService::class);
        $images->shouldNotReceive('upload');
        $images->shouldNotReceive('delete');

        $this->actingAs($actor)->patchJson(route($role.'.products.update', $product), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('stock');

        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertDatabaseMissing('product_slugs', ['product_id' => $product->id]);
    }

    #[DataProvider('stockEditors')]
    public function test_stock_edit_without_original_stock_is_rejected(string $role): void
    {
        [$actor, $product, $payload] = $this->stockEditFixture($role);
        unset($payload['original_stock']);
        $before = $product->fresh()->getAttributes();
        $this->actingAs($actor)->patchJson(route($role.'.products.update', $product), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('original_stock');
        $this->assertSame($before, $product->fresh()->getAttributes());
    }

    private function stockEditFixture(string $role): array
    {
        Storage::fake('public');
        \Illuminate\Support\Facades\Notification::fake();
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $seller = User::factory()->seller()->create();
        $actor = $role === 'seller' ? $seller : User::factory()->admin()->create();
        $product = $this->product($seller);
        return [$actor, $product, [
            'title' => $product->title, 'price' => $product->price,
            'stock' => '5', 'original_stock' => '5', 'description' => 'Updated description',
            'status' => $product->status, 'category_id' => $product->category_id,
            'country_id' => $product->city->country_id, 'city_id' => $product->city_id,
        ]];
    }

    public static function mutations(): array
    {
        return array_map(fn ($action) => [$action], ['edit', 'main', 'gallery-add', 'gallery-update-delete', 'gallery-delete', 'delete']);
    }

    #[DataProvider('mutations')]
    public function test_old_owner_cannot_mutate_a_reassigned_product_or_its_files(string $action): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $newOwner = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();
        $product = $this->product($seller);
        $this->actingAs($seller);
        $this->assertTrue(Gate::allows($action === 'delete' ? 'delete' : 'update', $product));
        app(ProductService::class)->update($product->fresh(), ['user_id' => $newOwner->id], actor: $admin);
        $before = $product->fresh()->getAttributes();
        $files = Storage::disk('public')->allFiles();
        $images = $this->mock(ImageService::class);
        $images->shouldNotReceive('upload');
        $images->shouldNotReceive('delete');
        try {
            SellerProductMutation::run($seller, $product, $action);
            $this->fail('A preauthorized former owner must be rejected.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(403, $exception->status() ?? 403);
        }
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame($files, Storage::disk('public')->allFiles());
        $this->assertDatabaseMissing('product_slugs', ['product_id' => $product->id]);
        $this->assertDatabaseMissing('attribute_values', ['product_id' => $product->id]);
    }

    public static function corrections(): array
    {
        return array_map(fn ($action) => [$action], ['edit', 'main', 'gallery-add', 'gallery-update-delete', 'gallery-delete']);
    }

    #[DataProvider('corrections')]
    public function test_blocked_product_can_be_corrected_without_republication(string $action): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();
        $product = $this->product($seller);
        $report = ProductReport::create([
            'product_id' => $product->id, 'user_id' => User::factory()->create()->id, 'reason' => 'Incorrect description',
        ]);
        $this->actingAs($admin)->post(route('admin.product-reports.hide-product', $report), [
            'resolution' => 'Correct the product before publication.',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Product::STATUS_BLOCKED, $product->fresh()->status);
        SellerProductMutation::run($seller, $product, $action);
        $current = $product->fresh();
        $this->assertSame(Product::STATUS_BLOCKED, $current->status);
        $this->assertSame($seller->id, $current->user_id);
        if ($action !== 'gallery-delete') {
            $this->assertSame('Seller correction', $current->title);
        }
        if ($action === 'main') {
            $this->assertNotSame($product->image, $current->image);
            Storage::disk('public')->assertExists($current->image);
        } elseif ($action === 'gallery-add') {
            $this->assertCount(2, $current->gallery);
            Storage::disk('public')->assertExists($current->gallery);
        } elseif (in_array($action, ['gallery-delete', 'gallery-update-delete'], true)) {
            $this->assertSame([], $current->gallery);
        }
    }

    public function test_stale_blocked_model_does_not_reblock_an_admin_restored_product(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();
        $product = $this->product($seller);
        $product->update(['status' => Product::STATUS_BLOCKED]);
        app(ProductService::class)->update($product->fresh(), ['status' => Product::STATUS_ACTIVE], actor: $admin);
        SellerProductMutation::run($seller, $product, 'edit');
        $this->assertSame(Product::STATUS_ACTIVE, $product->fresh()->status);
    }

    public function test_new_owner_can_edit_after_reassignment_and_admin_can_restore(): void
    {
        Storage::fake('public');
        $oldOwner = User::factory()->seller()->create();
        $newOwner = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();
        $product = $this->product($oldOwner);
        app(ProductService::class)->update($product, ['user_id' => $newOwner->id, 'status' => Product::STATUS_BLOCKED], actor: $admin);
        SellerProductMutation::run($newOwner, $product->fresh(), 'edit');
        $this->assertSame(Product::STATUS_BLOCKED, $product->fresh()->status);
        app(ProductService::class)->update($product->fresh(), ['status' => Product::STATUS_ACTIVE], actor: $admin);
        $this->assertSame(Product::STATUS_ACTIVE, $product->fresh()->status);
        $this->assertSame($newOwner->id, $product->fresh()->user_id);
    }

    private function product(User $seller): Product
    {
        $country = Country::create(['name' => 'Mutation country', 'currency' => 'MDL']);
        $city = City::create(['name' => 'Mutation city', 'country_id' => $country->id]);
        foreach (['products/medium/original.webp', 'products/gallery/medium/original.webp'] as $path) {
            Storage::disk('public')->put($path, 'original');
            Storage::disk('public')->put(ImageService::thumbPath($path), 'thumbnail');
        }
        return Product::create([
            'user_id' => $seller->id, 'title' => 'Original product', 'price' => 100, 'stock' => 5,
            'category_id' => Category::factory()->create()->id, 'city_id' => $city->id,
            'currency_base' => 'MDL', 'status' => Product::STATUS_ACTIVE,
            'image' => 'products/medium/original.webp', 'gallery' => ['products/gallery/medium/original.webp'],
        ]);
    }
}
