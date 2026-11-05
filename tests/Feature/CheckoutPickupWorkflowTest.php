<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutPickupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private array $baselineCounts;

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

        foreach (['orders', 'order_items', 'order_events', 'user_notifications'] as $table) {
            $this->baselineCounts[$table] = DB::table($table)->count();
        }
    }

    private function assertAddedRows(string $table, int $count): void
    {
        $this->assertDatabaseCount($table, $this->baselineCounts[$table] + $count);
    }

    public function test_new_pickup_order_starts_unpaid_and_ignores_forged_workflow_fields(): void
    {
        [$buyer, $seller, $product] = $this->fixture();
        $this->prepare($buyer);

        $this->post(route('checkout.create'), [
            'checkout_token' => session('checkout_token'),
            'payment_method' => 'card',
            'delivery_method' => 'pickup',
            'workflow_version' => 1,
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_SELLER_CONFIRMED,
            'paid_at' => now()->toDateTimeString(),
            'accepted_at' => now()->toDateTimeString(),
            'ready_for_pickup_at' => now()->toDateTimeString(),
            'buyer_confirmed_at' => now()->toDateTimeString(),
            'delivered_at' => now()->toDateTimeString(),
            'completed_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $order = Order::where('user_id', $buyer->id)->with('items')->sole();
        $this->assertSame(Order::WORKFLOW_PICKUP, $order->workflow_version);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame('card', $order->payment_method);
        $this->assertSame('pickup', $order->delivery_method);
        foreach (['paid_at', 'accepted_at', 'ready_for_pickup_at', 'buyer_confirmed_at',
            'delivered_at', 'completed_at', 'canceled_at', 'confirmation_requested_at'] as $field) {
            $this->assertNull($order->$field, $field);
        }
        $this->assertNull($order->address_id);
        $this->assertSame('100.00', $order->total_price);
        $this->assertSame(0.0, $order->delivery_cost);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame('checkout', $order->items->sole()->identity_snapshot_source);
        $this->assertSame('order_created', $order->events()->sole()->event_type);
        $this->assertSame($buyer->id, $order->events()->sole()->actor_id);
        $this->assertSame($seller->id, $order->seller_id);
    }

    public function test_checkout_rejects_every_unavailable_method_and_keeps_token(): void
    {
        [$buyer, , $product] = $this->fixture();
        $response = $this->prepare($buyer);
        $response->assertSee('value="pickup"', false)
            ->assertSee('value="cash"', false)
            ->assertSee('value="card"', false)
            ->assertDontSee('value="courier"', false)
            ->assertDontSee('value="bank_transfer"', false)
            ->assertDontSee('name="address_id"', false);
        $token = session('checkout_token');

        foreach (['courier', 'post', 'express'] as $delivery) {
            $this->post(route('checkout.create'), [
                'checkout_token' => $token, 'payment_method' => 'cash', 'delivery_method' => $delivery,
            ])->assertSessionHasErrors('delivery_method');
        }
        foreach (['bank_transfer', 'online'] as $payment) {
            $this->post(route('checkout.create'), [
                'checkout_token' => $token, 'payment_method' => $payment, 'delivery_method' => 'pickup',
            ])->assertSessionHasErrors('payment_method');
        }
        $this->post(route('checkout.create'), [
            'checkout_token' => $token, 'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'address_id' => 123,
        ])->assertSessionHasErrors('address_id');

        $this->assertAddedRows('orders', 0);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame($token, session('checkout_token'));
        $this->post(route('checkout.create'), [
            'checkout_token' => $token, 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ])->assertRedirect();
        $this->assertSame('cash', Order::where('user_id', $buyer->id)->sole()->payment_method);
    }

    public function test_multi_seller_pickup_preserves_currency_and_splits_orders_without_delivery_charge(): void
    {
        config(['currency.prb_per_mdl' => 2.0]);
        [$buyer, $firstSeller, $firstProduct] = $this->fixture(100, 'PRB');
        $secondSeller = User::factory()->create(['role' => 'seller']);
        $secondProduct = $this->product($secondSeller, 30, 'MDL');
        CartItem::create(['user_id' => $buyer->id, 'product_id' => $secondProduct->id, 'qty' => 1]);
        $this->prepare($buyer, 'MDL');

        $this->post(route('checkout.create'), [
            'checkout_token' => session('checkout_token'),
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ])->assertRedirect(route('orders.index'));

        $orders = Order::where('user_id', $buyer->id)->with('items')->orderBy('seller_id')->get()->keyBy('seller_id');
        $this->assertCount(2, $orders);
        $this->assertSame('50.00', $orders[$firstSeller->id]->total_price);
        $this->assertSame('30.00', $orders[$secondSeller->id]->total_price);
        $this->assertSame('PRB', $orders[$firstSeller->id]->items->sole()->source_currency);
        $this->assertSame('MDL', $orders[$secondSeller->id]->items->sole()->source_currency);
        foreach ($orders as $order) {
            $this->assertSame('MDL', $order->currency);
            $this->assertSame(Order::WORKFLOW_PICKUP, $order->workflow_version);
            $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
            $this->assertSame(0.0, $order->delivery_cost);
            $this->assertSame(1, $order->events()->count());
        }
        $this->assertSame(2, $firstProduct->fresh()->stock);
        $this->assertSame(2, $secondProduct->fresh()->stock);
    }

    public function test_second_order_created_event_failure_rolls_back_all_sellers_and_allows_same_token_retry(): void
    {
        [$buyer, , $firstProduct] = $this->fixture();
        $secondSeller = User::factory()->create(['role' => 'seller']);
        $secondProduct = $this->product($secondSeller, 75, 'PRB');
        CartItem::create(['user_id' => $buyer->id, 'product_id' => $secondProduct->id, 'qty' => 1]);
        $this->prepare($buyer);
        $token = session('checkout_token');
        $cacheKey = 'checkout:used:'.hash('sha256', $token);
        $eventWrites = 0;

        DB::listen(function ($query) use (&$eventWrites): void {
            if (str_starts_with(strtolower($query->sql), 'insert into `order_events`')
                && ++$eventWrites === 2) {
                throw new \RuntimeException('Injected second order_created event failure');
            }
        });

        $this->withoutExceptionHandling();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Injected second order_created event failure');
        try {
            $this->post(route('checkout.create'), [
                'checkout_token' => $token,
                'payment_method' => 'cash', 'delivery_method' => 'pickup',
            ]);
        } finally {
            $this->assertSame(2, $eventWrites, 'The second seller must reach the event insert.');
            $this->assertAddedRows('orders', 0);
            $this->assertAddedRows('order_items', 0);
            $this->assertAddedRows('order_events', 0);
            $this->assertAddedRows('user_notifications', 0);
            $this->assertSame([3, 3], Product::whereIn('id', [$firstProduct->id, $secondProduct->id])
                ->orderBy('id')->pluck('stock')->all());
            $this->assertSame($token, session('checkout_token'));
            $this->assertFalse(Cache::has($cacheKey), 'Failed checkout must release its replay reservation.');
            // F11 persists replay ownership in SQL, not in the historical cache key.
            $this->assertDatabaseMissing('checkout_attempts', ['token_hash' => hash('sha256', $token)]);

            // The listener fails only once; retrying the same token must create both orders, not a partial duplicate.
            $this->withExceptionHandling();
            $this->post(route('checkout.create'), [
                'checkout_token' => $token,
                'payment_method' => 'cash', 'delivery_method' => 'pickup',
            ])->assertRedirect(route('orders.index'))->assertSessionHasNoErrors();
            $this->assertAddedRows('orders', 2);
            $this->assertAddedRows('order_items', 2);
            $this->assertAddedRows('order_events', 2);
            $attempt = DB::table('checkout_attempts')->where('token_hash', hash('sha256', $token))->sole();
            $this->assertSame($buyer->id, (int) $attempt->buyer_id);
            $this->assertSame(
                Order::where('user_id', $buyer->id)->orderBy('id')->pluck('id')->all(),
                json_decode($attempt->order_ids, true, flags: JSON_THROW_ON_ERROR),
            );
            $this->assertSame([2, 2], Product::whereIn('id', [$firstProduct->id, $secondProduct->id])
                ->orderBy('id')->pluck('stock')->all());
            $this->assertSame(['75.00', '100.00'], Order::where('user_id', $buyer->id)->orderBy('total_price')->pluck('total_price')->all());
            foreach (Order::where('user_id', $buyer->id)->get() as $order) {
                $this->assertSame('PRB', $order->currency);
                $this->assertSame(Order::WORKFLOW_PICKUP, $order->workflow_version);
                $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
                $this->assertSame(0.0, $order->delivery_cost);
                $this->assertSame('order_created', $order->events()->sole()->event_type);
            }
        }
    }

    private function fixture(float $price = 100, string $currency = 'PRB'): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->product($seller, $price, $currency);
        CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'qty' => 1]);

        return [$buyer, $seller, $product];
    }

    private function product(User $seller, float $price, string $currency): Product
    {
        $suffix = str()->lower(str()->random(12));

        return Product::create([
            'user_id' => $seller->id, 'title' => 'Pickup '.$suffix,
            'slug' => 'pickup-'.$suffix, 'sku' => 'PICKUP-'.strtoupper($suffix),
            'price' => $price, 'currency_base' => $currency, 'stock' => 3,
            'status' => Product::STATUS_ACTIVE,
            // No image is part of this scenario; do not snapshot a shared default file.
            'image' => '',
        ]);
    }

    private function prepare(User $buyer, string $currency = 'PRB')
    {
        $this->actingAs($buyer)->withSession(['currency' => $currency])
            ->post(route('checkout.prepare'))->assertRedirect(route('checkout.confirm'));

        return $this->get(route('checkout.confirm'))->assertOk();
    }
}
