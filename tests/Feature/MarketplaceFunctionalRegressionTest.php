<?php

namespace Tests\Feature;

use App\Models\{Attribute, AttributeValue, Category, Order, Product, User};
use App\Services\OrderPickupWorkflow;
use Illuminate\Support\Facades\{Blade, Cache, DB, Http, Mail, Notification, ParallelTesting, Queue, Storage};
use Illuminate\View\Component;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\{TestDatabaseSafety, TestFileSafety};
use Tests\TestCase;

class MarketplaceFunctionalRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertFalse(ParallelTesting::token(), 'Run existing-schema targets sequentially.');
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));
        $this->assertInstanceOf(\Illuminate\Cache\ArrayStore::class, Cache::getStore());
        $this->assertSame('array', config('session.driver'));
        $this->assertInstanceOf(\Illuminate\Session\ArraySessionHandler::class, session()->driver()->getHandler());

        $this->isolateFilesAndViews();
        config(['queue.default' => 'sync', 'mail.default' => 'array', 'logging.default' => 'null']);
        Notification::fake();
        Mail::fake();
        Queue::fake();
        Http::preventStrayRequests();

        $connection = DB::connection();
        TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
        $this->assertSame(0, $connection->transactionLevel());
        $this->assertInstanceOf(\App\Database\SafeDatabaseTransactionsManager::class, app('db.transactions'));
        DB::beginTransaction();
        $this->rollBackTestTransactionsBeforeApplicationDestroyed();

        $lock = tmpfile();
        $this->assertIsResource($lock);
        // Keep the test-owned inode alive until all rollback callbacks release it.
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        config(['backup.lock_path' => stream_get_meta_data($lock)['uri']]);
    }

    private function isolateFilesAndViews(): void
    {
        // A resolved compiler retains its constructor's cache path. Do not silently
        // redirect config while an existing engine could still write shared files.
        $this->assertFalse($this->app->resolved('blade.compiler'), 'Blade must be isolated before compiler resolution.');
        $root = storage_path('framework/testing/group19-marketplace-'.bin2hex(random_bytes(16)));
        TestFileSafety::assertDirectory($root, mustExist: false);
        $this->assertFalse(file_exists($root) || is_link($root));
        $this->assertTrue(mkdir($root, 0700, true));
        TestFileSafety::assertDirectory($root);

        foreach (['views', 'public', 'private', 'review_images'] as $directory) {
            $path = $root.'/'.$directory;
            TestFileSafety::assertDirectory($path, mustExist: false);
            $this->assertTrue(mkdir($path, 0700));
            TestFileSafety::assertDirectory($path);
        }

        // Both inline component source files and compiled PHP use this directory.
        config(['view.compiled' => $root.'/views']);
        Component::flushCache();
        Component::forgetFactory();
        $this->beforeApplicationDestroyed(static function (): void {
            Component::flushCache();
            Component::forgetFactory();
        });
        $compiler = app('blade.compiler');
        $this->assertSame($root.'/views', dirname($compiler->getCompiledPath(resource_path('views/components/product-card.blade.php'))));
        $this->assertSame($compiler, app('view.engine.resolver')->resolve('blade')->getCompiler());

        foreach (['public' => 'public', 'local' => 'private', 'review_images' => 'review_images'] as $disk => $directory) {
            config(['filesystems.disks.'.$disk => [
                'driver' => 'local', 'root' => $root.'/'.$directory, 'throw' => true,
                'visibility' => $disk === 'public' ? 'public' : 'private',
            ]]);
            Storage::forgetDisk($disk);
            $this->assertSame($root.'/'.$directory, rtrim(Storage::disk($disk)->path(''), '/\\'));
        }
        config(['filesystems.default' => 'local']);
        // Keep unique artifacts for inspection. No shared directory, symlink or
        // junction is created or recursively cleaned, including after failures.
    }

    public static function deletionStates(): array
    {
        $cases = [];
        foreach (['buyer', 'seller'] as $participant) {
            foreach ([null, Order::WORKFLOW_PICKUP] as $workflow) {
                foreach (Order::filterStatuses() as $status) {
                    $cases["$participant-".($workflow ?? 'legacy')."-$status"] = [$participant, $workflow, $status];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('deletionStates')]
    public function test_deletion_respects_participant_and_order_state(string $participant, ?int $workflow, string $status): void
    {
        [$order, $buyer, $seller] = $this->order($status, $workflow);
        $user = $participant === 'buyer' ? $buyer : $seller;
        $profile = route($participant === 'buyer' ? 'buyer.profile' : 'profile.edit');
        $before = $order->getRawOriginal();
        $response = $this->actingAs($user)->from($profile)->delete('/profile', ['password' => 'password']);
        if (in_array($status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELED], true)) {
            $response->assertSessionHasNoErrors()->assertRedirect('/');
            $this->assertSoftDeleted($user);
            self::assertNull(User::withTrashed()->find($user->id)->email);
        } else {
            $response->assertRedirect($profile)->assertSessionHasErrors('user');
            self::assertFalse($user->fresh()->trashed());
            self::assertSame($user->email, $user->fresh()->email);
            $this->assertAuthenticatedAs($user);
            $this->get($profile)->assertOk()->assertSee('Нельзя удалить аккаунт');
        }
        self::assertSame($before, $order->fresh()->getRawOriginal());
    }

    public function test_legacy_received_order_allows_deletion_after_receipt_confirmation(): void
    {
        [$order, $buyer, $seller] = $this->order(Order::STATUS_DELIVERED, null);
        DB::table('orders')->where('id', $order->id)->update(['buyer_confirmed_at' => now()]);
        $buyer->delete();
        $seller->delete();
        $this->assertSoftDeleted($buyer);
        $this->assertSoftDeleted($seller);
    }

    public function test_admin_and_direct_model_deletion_cannot_bypass_active_orders(): void
    {
        [$order, $buyer] = $this->order();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $buyer))->assertSessionHasErrors('user');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $buyer->delete();
    }

    public function test_seller_can_buy_and_complete_own_purchase_but_not_other_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'seller']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->product($seller);
        $this->actingAs($buyer)->post(route('checkout.quick', $product))->assertRedirect(route('checkout.confirm'));
        $this->post(route('checkout.create'), ['checkout_token' => session('checkout_token'), 'payment_method' => 'cash', 'delivery_method' => 'pickup'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('user_id', $buyer->id)->sole();
        self::assertSame($seller->id, $order->seller_id);
        $receipt = route('orders.confirmDelivery', $order);
        $cancel = route('orders.requestCancellation', $order);
        $this->post($receipt)->assertSessionHasErrors('status');
        $this->post($cancel, ['cancellation_reason' => 'Планы изменились'])->assertSessionHasNoErrors();
        self::assertNotNull($order->fresh()->cancellation_requested_at);
        $this->actingAs($seller)->post($receipt)->assertForbidden();
        $this->post($cancel, ['cancellation_reason' => 'Чужой заказ'])->assertForbidden();
        $this->post(route('checkout.quick', $product))->assertSessionHas('error');
        $workflow = app(OrderPickupWorkflow::class);
        $workflow->sellerRejectCancellation($order, $seller, 'Заказ будет выдан');
        $workflow->sellerUpdate($order, $seller, Order::STATUS_PROCESSING);
        $workflow->sellerUpdate($order, $seller, Order::STATUS_READY_FOR_PICKUP);
        $this->actingAs($buyer)->post(route('seller.orders.confirmPayment', $order))->assertForbidden();
        $this->post($receipt)->assertSessionHasNoErrors();
        self::assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
        $workflow->sellerConfirmPayment($order, $seller);
        self::assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->post($receipt)->assertSessionHasNoErrors();
        $this->post($cancel, ['cancellation_reason' => 'Уже завершён'])->assertSessionHasErrors('status');
    }

    public function test_numeric_ranges_and_invalid_boundaries_preserve_other_filters(): void
    {
        $category = Category::factory()->create();
        $number = Attribute::create(['name' => 'Numeric regression', 'type' => 'number', 'is_filterable' => true]);
        $select = Attribute::create(['name' => 'Finish regression', 'type' => 'select', 'is_filterable' => true, 'options' => ['Matte', 'Gloss']]);
        $text = Attribute::create(['name' => 'Text regression', 'type' => 'text', 'is_filterable' => true]);
        $category->attributes()->attach([$number->id, $select->id, $text->id]);
        $seller = User::factory()->create(['role' => 'seller']);
        $ids = [];
        foreach (['-2.5', '2', '2.25', '9', '10', '10.5', '100'] as $value) {
            $product = $this->product($seller, ['category_id' => $category->id]);
            $ids[$value] = $product->id;
            foreach ([$number->id => $value, $select->id => 'Matte', $text->id => 'plain <text>'] as $attributeId => $stored) {
                AttributeValue::create(['product_id' => $product->id, 'attribute_id' => $attributeId, 'value' => $stored]);
            }
        }
        foreach ([
            [['from' => '2', 'to' => '10'], ['2', '2.25', '9', '10']],
            [['from' => '2.1', 'to' => '2.3'], ['2.25']],
            [['to' => '-2.4'], ['-2.5']],
            [['from' => '10.5'], ['10.5', '100']],
        ] as [$range, $expected]) {
            $response = $this->get(route('category.show', ['slug' => $category->slug, 'filters' => [$number->id => $range, $select->id => ['Matte'], $text->id => ['plain <text>']]]))->assertOk();
            self::assertEqualsCanonicalizing(array_map(fn ($value) => $ids[$value], $expected), $response->viewData('products')->pluck('id')->all());
        }
        foreach ([['from' => '10', 'to' => '2'], ['from' => '1 OR 1=1'], ['to' => ['nested']], ['from' => '1e999'], ['from' => '10000000000000']] as $invalid) {
            $response = $this->get(route('category.show', ['slug' => $category->slug, 'filters' => [$number->id => $invalid, $select->id => ['Gloss']]]))->assertOk();
            self::assertSame([$select->id => ['Gloss']], $response->viewData('activeFilters'));
            self::assertSame(0, $response->viewData('products')->total());
        }
    }

    public function test_prices_keep_cents_and_selected_currency_in_all_requested_views(): void
    {
        [$order, $buyer, $seller] = $this->order(Order::STATUS_COMPLETED);
        $product = $this->product($seller, ['price' => 10.49, 'old_price' => 12.99, 'currency_base' => 'MDL',
            'price_mdl' => 10.49, 'old_price_mdl' => 12.99, 'price_prb' => 20.98, 'old_price_prb' => 25.98]);
        foreach (['MDL' => ['10,49', '12,99', 'L'], 'PRB' => ['20,98', '25,98', '₽']] as $currency => [$price, $old, $symbol]) {
            session(['currency' => $currency]);
            foreach ([
                Blade::render('<x-product-card :p="$product" />', compact('product')),
                view('components.product.buy-box', ['product' => $product, 'isFav' => false])->render(),
                view('components.product.related', ['items' => collect([$product])])->render(),
            ] as $html) {
                $plain = html_entity_decode(strip_tags($html));
                $plain = str_replace("\u{00a0}", ' ', $plain);
                self::assertStringContainsString("$price $symbol", $plain);
                self::assertStringContainsString("$old $symbol", $plain);
            }
            $html = $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->getContent();
            $recommendations = substr($html, strpos($html, 'Продолжить покупки'));
            self::assertStringContainsString("$price $symbol", $recommendations);
        }
        $html = view('components.product.buy-box', ['product' => $product, 'isFav' => false])->render();
        self::assertStringContainsString('Самовывоз: получение и оплата у продавца', $html);
        self::assertStringNotContainsString('Доставка: по договорённости', $html);
    }

    public function test_currency_fallback_renders_base_amount_and_symbol_in_all_views(): void
    {
        [$order, $buyer, $seller] = $this->order(Order::STATUS_COMPLETED);
        $product = $this->product($seller, ['price' => 10.49, 'old_price' => 12.99, 'currency_base' => 'MDL']);
        session(['currency' => 'PRB']);
        self::assertSame(['amount' => 10.49, 'code' => 'MDL', 'symbol' => 'L'], $product->price_for_current_currency);
        self::assertSame(['amount' => 12.99, 'code' => 'MDL', 'symbol' => 'L'], $product->old_price_for_current_currency);
        foreach ([
            Blade::render('<x-product-card :p="$product" />', compact('product')),
            view('components.product.buy-box', ['product' => $product, 'isFav' => false])->render(),
            view('components.product.related', ['items' => collect([$product])])->render(),
        ] as $html) {
            $plain = str_replace("\u{00a0}", ' ', html_entity_decode(strip_tags($html)));
            self::assertStringContainsString('10,49 L', $plain);
            self::assertStringContainsString('12,99 L', $plain);
            self::assertStringNotContainsString('10,49 ₽', $plain);
        }
        $html = $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->getContent();
        self::assertStringContainsString('10,49 L', substr($html, strpos($html, 'Продолжить покупки')));
    }

    public function test_currency_fallback_does_not_compare_old_price_in_another_currency(): void
    {
        $product = new Product(['price' => 10.49, 'old_price' => 100, 'currency_base' => 'MDL', 'price_prb' => 20.98]);
        session(['currency' => 'PRB']);
        self::assertSame('PRB', $product->price_for_current_currency['code']);
        self::assertNull($product->old_price_for_current_currency);
        self::assertNull($product->discount_percent);
        $product->old_price_prb = 25.98;
        self::assertSame(19, $product->discount_percent);
        $product->old_price_prb = null;
        $product->price_prb = 0;
        self::assertSame(0.0, $product->price_for_current_currency['amount']);
        self::assertSame('PRB', $product->price_for_current_currency['code']);
        $product->price_prb = null;
        $product->currency_base = 'RUB';
        self::assertSame('PRB', $product->price_for_current_currency['code']);
        self::assertSame(100.0, $product->old_price_for_current_currency['amount']);
    }

    public function test_participant_locks_precede_order_lock_for_reminder(): void
    {
        [$order, $buyer, $seller] = $this->order(Order::STATUS_READY_FOR_PICKUP);
        $locks = [];
        DB::listen(function ($query) use (&$locks): void {
            if (str_contains(strtolower($query->sql), 'for update')) {
                $locks[] = strtolower($query->sql);
            }
        });
        self::assertTrue(app(OrderPickupWorkflow::class)->sellerRequestReceiptConfirmation($order, $seller));
        self::assertStringContainsString('from `users`', $locks[0]);
        self::assertStringContainsString('order by `id` asc', $locks[0]);
        self::assertStringContainsString('from `orders`', $locks[1]);
        self::assertNotNull($order->fresh()->confirmation_requested_at);
    }

    public function test_successful_checkout_replay_keeps_orders_stock_events_and_notifications(): void
    {
        $buyer = User::factory()->create(['role' => 'seller']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->product($seller);
        $this->actingAs($buyer)->post(route('checkout.quick', $product))->assertRedirect();
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        $payload = ['checkout_token' => $snapshot['checkout_token'], 'payment_method' => 'cash', 'delivery_method' => 'pickup'];
        $this->post(route('checkout.create'), $payload)->assertSessionHasNoErrors();
        $order = Order::where('user_id', $buyer->id)->sole();
        $events = $order->events()->orderBy('id')->get()->toArray();
        $notifications = DB::table('user_notifications')->where('user_id', $seller->id)->orderBy('id')->get()->toArray();
        self::assertCount(1, $events);
        self::assertCount(1, $notifications);
        $this->withSession($snapshot)->post(route('checkout.create'), $payload)
            ->assertRedirect(route('orders.show', $order))->assertSessionHasNoErrors();
        self::assertSame(1, Order::where('user_id', $buyer->id)->count());
        self::assertSame(4, $product->fresh()->stock);
        self::assertSame($events, $order->events()->orderBy('id')->get()->toArray());
        self::assertEquals($notifications, DB::table('user_notifications')->where('user_id', $seller->id)->orderBy('id')->get()->toArray());
        self::assertSame(1, DB::table('checkout_attempts')->where('token_hash', hash('sha256', $snapshot['checkout_token']))->count());
    }

    private function order(string $status = Order::STATUS_PENDING, ?int $workflow = Order::WORKFLOW_PICKUP): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = new Order(['user_id' => $buyer->id, 'seller_id' => $seller->id, 'number' => Order::generateNumber(), 'status' => $status,
            'payment_method' => 'cash', 'delivery_method' => 'pickup', 'total_price' => 100, 'currency' => 'MDL']);
        $order->forceFill(['workflow_version' => $workflow, 'payment_status' => Order::PAYMENT_UNPAID])->save();
        return [$order->refresh(), $buyer, $seller];
    }

    private function product(User $seller, array $values = []): Product
    {
        return Product::create(array_merge(['user_id' => $seller->id, 'title' => 'Regression '.str()->random(12), 'price' => 100,
            'currency_base' => 'MDL', 'stock' => 5, 'status' => Product::STATUS_ACTIVE], $values));
    }
}
