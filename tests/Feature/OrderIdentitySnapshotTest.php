<?php

namespace Tests\Feature;

use App\Models\{CartItem, Order, OrderItem, Product, User};
use App\Services\{OrderIdentitySnapshot, ProductService};
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Foundation\Testing\{RefreshDatabase, RefreshDatabaseState};
use Illuminate\Support\Facades\{DB, Notification, Storage};
use Tests\TestCase;

class OrderIdentitySnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(int $count = 1): array
    {
        Storage::fake('public');
        Notification::fake();
        $buyer = User::factory()->create(['role' => 'buyer']);
        $products = [];
        for ($i = 0; $i < $count; $i++) {
            $seller = User::factory()->create(['role' => 'seller', 'name' => 'Original seller '.$i]);
            $product = Product::create([
                'user_id' => $seller->id, 'title' => 'Original identity A'.$i,
                'sku' => 'SKU-A'.$i, 'slug' => 'snapshot-'.$i, 'price' => 100,
                'currency_base' => 'PRB', 'stock' => 5, 'status' => Product::STATUS_ACTIVE,
                'image' => 'products/original-'.$i.'.webp',
            ]);
            Storage::disk('public')->put($product->image, 'original-image-'.$i);
            CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'qty' => 1]);
            $products[] = $product;
        }
        $this->actingAs($buyer)->withSession(['currency' => 'PRB'])->post(route('checkout.prepare'))->assertRedirect();
        $this->get(route('checkout.confirm'))->assertOk();
        return [$buyer, $products];
    }

    private function submit()
    {
        return $this->post(route('checkout.create'), [
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'checkout_token' => session('checkout_token'),
            'product_title' => 'FORGED', 'product_sku' => 'FORGED',
            'product_image_path' => 'FORGED', 'seller_snapshot' => ['name' => 'FORGED'],
        ]);
    }

    public function test_locked_checkout_identity_survives_edits_in_views_search_and_disputes(): void
    {
        [$buyer, [$product]] = $this->prepare();
        // Poison the session identity too; capture must use the locked Product.
        $cart = session('checkout_cart');
        $cart[0]['title'] = 'FORGED';
        $cart[0]['image'] = 'FORGED';
        $this->withSession(['checkout_cart' => $cart])->submit()->assertSessionHasNoErrors();
        $order = Order::sole();
        $item = $order->items()->sole();
        $this->assertSame('Original identity A0', $item->product_title);
        $this->assertSame('SKU-A0', $item->product_sku);
        $this->assertSame('checkout', $item->identity_snapshot_source);
        $this->assertSame('original-image-0', Storage::disk('public')->get($item->product_image_path));
        $money = $item->only(['price', 'total', 'source_price', 'source_currency', 'exchange_rate']);
        $product->update(['title' => 'Replacement identity B', 'sku' => 'SKU-B', 'price' => 200]);
        $seller = $order->seller;
        $seller->update(['name' => 'Replacement seller']);
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([[$buyer, 'orders.show'], [$seller, 'seller.orders.show'], [$admin, 'admin.orders.show']] as [$user, $route]) {
            $this->actingAs($user)->get(route($route, $order))->assertOk()->assertSee('Original identity A0')
                ->assertDontSee('Replacement identity B')->assertDontSee('FORGED');
        }
        foreach ([[$buyer, 'orders.index'], [$seller, 'seller.orders.index'], [$admin, 'admin.orders.index']] as [$user, $route]) {
            $this->actingAs($user)->get(route($route, ['q' => 'Original identity A0']))->assertOk()->assertSee($order->number);
            $this->get(route($route, ['q' => 'Replacement identity B']))->assertOk()->assertDontSee($order->number);
        }
        $order->disputes()->create(['user_id' => $buyer->id, 'seller_id' => $seller->id,
            'status' => 'open', 'reason' => 'wrong_item', 'details' => 'Test dispute']);
        $this->actingAs($admin)->get(route('admin.disputes.index'))->assertOk()->assertSee('Original identity A0')->assertDontSee('Replacement identity B');
        $this->assertSame($money, $item->fresh()->only(array_keys($money)));
        $this->assertSame('Original seller 0', $order->fresh()->historical_seller_name);
        $this->actingAs($buyer)->post(route('orders.chat.product', [$order, $product]))->assertRedirect();
        $conversation = \App\Models\Conversation::where('order_id', $order->id)->sole();
        $this->get(route('chats.show', $conversation))->assertOk()->assertSee('Original identity A0')->assertDontSee('Replacement identity B');
        $this->assertStringContainsString('Original identity A0', $conversation->messages()->sole()->body);
        $this->actingAs($admin)->get(route('admin.chats.show', $conversation))->assertOk()
            ->assertSee('Original identity A0')->assertDontSee('Replacement identity B');
    }

    public function test_chat_search_uses_order_identity_but_keeps_live_product_search_for_regular_chats(): void
    {
        [$buyer, [$product]] = $this->prepare();
        $this->submit()->assertSessionHasNoErrors();
        $order = Order::sole();
        // No messages: historical matches must come from the snapshot, not message text.
        $historical = \App\Models\Conversation::create([
            'buyer_id' => $buyer->id, 'seller_id' => $order->seller_id,
            'product_id' => $product->id, 'order_id' => $order->id,
            'context_key' => 'order:'.$order->id.':product:'.$product->id,
            'conversation_type' => 'marketplace',
        ]);
        $regular = \App\Models\Conversation::create([
            'buyer_id' => $buyer->id, 'seller_id' => $order->seller_id,
            'product_id' => $product->id, 'conversation_type' => 'marketplace',
        ]);
        $product->update(['title' => 'Replacement identity B', 'sku' => 'SKU-B']);
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([[$buyer, 'chats.index'], [$order->seller, 'chats.index'], [$admin, 'admin.chats.index']] as [$user, $route]) {
            foreach (['Original identity A0', 'SKU-A0'] as $search) {
                $this->actingAs($user)->get(route($route, ['q' => $search, 'mode' => 'marketplace']))->assertOk()
                    ->assertViewHas('conversations', fn ($rows) => $rows->pluck('id')->all() === [$historical->id]);
            }
            $this->get(route($route, ['q' => 'Replacement identity B', 'mode' => 'marketplace']))->assertOk()
                ->assertViewHas('conversations', fn ($rows) => $rows->pluck('id')->all() === [$regular->id]);
            if ($user->role !== 'admin') {
                $this->get(route($route, ['q' => 'SKU-B']))->assertOk()
                    ->assertViewHas('conversations', fn ($rows) => $rows->pluck('id')->all() === [$regular->id]);
            }
        }
    }

    public function test_image_copy_survives_product_image_cleanup_and_soft_delete(): void
    {
        [, [$product]] = $this->prepare();
        $this->submit()->assertSessionHasNoErrors();
        $item = OrderItem::sole();
        app(ProductService::class)->update($product, [], \Illuminate\Http\UploadedFile::fake()->image('new.png'));
        Storage::disk('public')->assertMissing('products/original-0.webp');
        Storage::disk('public')->assertExists($item->product_image_path);
        app(ProductService::class)->delete($product->fresh());
        $this->assertTrue($item->fresh()->product->trashed());
        $this->assertSame('original-image-0', Storage::disk('public')->get($item->product_image_path));
    }

    public function test_seller_anonymization_preserves_identity_and_image(): void
    {
        [$buyer, [$product]] = $this->prepare();
        $shop = $product->seller->shop()->create(['name' => 'Original shop']);
        $this->submit()->assertSessionHasNoErrors();
        $order = Order::sole();
        $shop->update(['name' => 'Replacement shop']);
        $order->seller->delete();
        $order = $order->fresh();
        $this->assertSame('Original shop', $order->historical_seller_name);
        $this->assertSame('Original seller 0', $order->seller_snapshot['name']);
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()
            ->assertSee('Original shop')->assertSee('Original identity A0')->assertDontSee('Replacement shop');
        Storage::disk('public')->assertExists($order->items->sole()->product_image_path);
    }

    public function test_multi_seller_snapshots_are_independent_and_ignore_client_values(): void
    {
        $this->prepare(2);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 2);
        foreach (Order::orderBy('id')->get() as $i => $order) {
            $item = $order->items->sole();
            $this->assertSame('Original identity A'.$i, $item->product_title);
            $this->assertSame('Original seller '.$i, $order->historical_seller_name);
            $this->assertSame(4, $item->product->stock);
        }
        $this->assertCount(2, Storage::disk('public')->files('order-snapshots'));
    }

    public function test_legacy_backfill_is_idempotent_and_marks_uncertain_data(): void
    {
        $this->prepare();
        $this->submit()->assertSessionHasNoErrors();
        $order = Order::sole();
        $item = $order->items->sole();
        DB::table('order_items')->where('id', $item->id)->update(array_fill_keys(OrderItem::IDENTITY_FIELDS, null));
        DB::table('orders')->where('id', $order->id)->update(['seller_snapshot' => null]);
        $this->artisan('orders:backfill-identity')->assertSuccessful();
        $item = $item->fresh();
        $snapshot = $item->only(OrderItem::IDENTITY_FIELDS);
        $this->assertSame('legacy_backfill', $item->identity_snapshot_source);
        $this->assertStringContainsString('не подтверждены', $item->historical_title);
        $this->assertStringContainsString('не подтверждены', $order->fresh()->historical_seller_name);
        $count = count(Storage::disk('public')->files('order-snapshots'));
        $item->product->update(['title' => 'Later title']);
        $this->artisan('orders:backfill-identity')->assertSuccessful();
        $this->assertSame($snapshot, $item->fresh()->only(OrderItem::IDENTITY_FIELDS));
        $this->assertCount($count, Storage::disk('public')->files('order-snapshots'));
    }

    public function test_quiet_saves_cannot_replace_snapshots(): void
    {
        $this->prepare();
        $this->submit()->assertSessionHasNoErrors();
        foreach ([OrderItem::sole(), Order::sole()] as $model) {
            $model->forceFill($model instanceof OrderItem ? ['product_title' => 'Forged'] : ['seller_snapshot' => ['name' => 'Forged']]);
            try {
                $model->saveQuietly();
                $this->fail('Snapshot replacement must be rejected');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('immutable', $e->getMessage());
            }
        }
    }

    public function test_missing_legacy_image_and_uninitialized_identity_do_not_fall_back_to_product(): void
    {
        $this->prepare();
        $this->submit()->assertSessionHasNoErrors();
        $item = OrderItem::sole();
        DB::table('order_items')->where('id', $item->id)->update(array_fill_keys(OrderItem::IDENTITY_FIELDS, null));
        Storage::disk('public')->delete($item->product->image);
        $this->assertStringContainsString('Название не сохранено', $item->fresh()->historical_title);
        $this->artisan('orders:backfill-identity')->assertSuccessful();
        $this->assertNull($item->fresh()->product_image_path);
        $this->assertSame('legacy_backfill', $item->fresh()->identity_snapshot_source);
    }

    public function test_mid_checkout_failure_removes_all_image_copies_and_restores_stock(): void
    {
        $this->prepare(2);
        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (str_starts_with($query->sql, 'insert into `order_items`') && ++$writes === 2) {
                throw new \RuntimeException('Injected snapshot failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->submit();
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Injected snapshot failure', $e->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], Storage::disk('public')->files('order-snapshots'));
        $this->assertSame([5, 5], Product::orderBy('id')->pluck('stock')->all());
        $this->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 2);
        $this->assertCount(2, Storage::disk('public')->files('order-snapshots'));
    }

    public function test_partial_image_copy_failure_rolls_back_order_and_removes_partial_file(): void
    {
        $this->prepare();
        $disk = Storage::disk('public');
        $mock = \Mockery::mock($disk);
        $mock->shouldReceive('copy')->once()->andReturnUsing(function ($source, $destination) use ($disk) {
            $disk->put($destination, 'partial');
            return false;
        });
        Storage::set('public', $mock);
        $this->withoutExceptionHandling();
        try {
            $this->submit();
            $this->fail('Expected image copy failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Unable to preserve order image.', $e->getMessage());
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame([], $disk->files('order-snapshots'));
        $this->assertSame(5, Product::sole()->stock);
        Storage::set('public', $disk);
    }

    public function test_real_outer_rollback_removes_copies_from_committed_child_checkout(): void
    {
        DB::rollBack();
        $manager = new DatabaseTransactionsManager;
        $this->app->instance('db.transactions', $manager);
        DB::connection()->setTransactionManager($manager);
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
        $this->prepare(2);
        $cart = session('checkout_cart');
        DB::beginTransaction();
        $this->submit()->assertSessionHasNoErrors();
        $this->assertCount(2, Storage::disk('public')->files('order-snapshots'));
        DB::rollBack();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame([], Storage::disk('public')->files('order-snapshots'));
        $this->assertSame([5, 5], Product::orderBy('id')->pluck('stock')->all());
        $this->withSession(['checkout_cart' => $cart])->get(route('checkout.confirm'))->assertOk();
        DB::beginTransaction();
        $this->submit()->assertSessionHasNoErrors();
        DB::commit();
        $this->assertDatabaseCount('orders', 2);
        $this->assertCount(2, Storage::disk('public')->files('order-snapshots'));
    }
}
