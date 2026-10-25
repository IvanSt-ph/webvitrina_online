<?php

namespace Tests\Feature;

use App\Models\{Conversation, Order, OrderDispute, Product, User, UserNotification};
use Illuminate\Support\Facades\{DB, Notification};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestDatabaseSafety;
use Tests\TestCase;

class OrderParticipantAccessRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Existing schema only; no migrations or database clearing.
        $connection = DB::connection();
        TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
        DB::beginTransaction();
        $this->rollBackTestTransactionsBeforeApplicationDestroyed();
        Notification::fake();
    }

    public static function buyerRoles(): array
    {
        return [['buyer'], ['seller']];
    }

    #[DataProvider('buyerRoles')]
    public function test_order_owner_can_open_product_chat_and_support(string $role): void
    {
        [$order, $buyer, $seller, $product] = $this->fixture($role);
        User::factory()->create(['role' => 'admin']);
        $this->actingAs($buyer)->post(route('orders.chat.product', [$order, $product]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $chat = Conversation::where('order_id', $order->id)->where('product_id', $product->id)->sole();
        self::assertSame($buyer->id, $chat->buyer_id);
        self::assertSame($seller->id, $chat->seller_id);
        $this->get(route('chats.show', $chat))->assertOk();
        $this->actingAs($seller)->get(route('chats.show', $chat))->assertOk();

        $this->actingAs($buyer)->post(route('orders.support', $order))
            ->assertRedirect()->assertSessionHasNoErrors();
        $support = Conversation::where('buyer_id', $buyer->id)
            ->where('conversation_type', Conversation::TYPE_SUPPORT)->sole();
        self::assertSame('admin', $support->seller->role);
        self::assertTrue($support->messages()->where('order_id', $order->id)->exists());
        $this->get(route('chats.show', $support))->assertOk();

        $unrelatedProduct = Product::create(['user_id' => $seller->id, 'title' => 'Not in order '.str()->random(12), 'price' => 10]);
        $this->post(route('orders.chat.product', [$order, $unrelatedProduct]))->assertNotFound();

        // The selling participant may read their product chat, but cannot act as its buyer.
        $this->actingAs($seller)->post(route('orders.chat.product', [$order, $product]))->assertForbidden();
        $this->post(route('orders.support', $order))->assertForbidden();
        $this->get(route('chats.show', $support))->assertNotFound();

        foreach (['buyer', 'seller', 'admin'] as $outsiderRole) {
            $outsider = User::factory()->create(['role' => $outsiderRole]);
            $beforeConversations = Conversation::count();
            $beforeMessages = DB::table('messages')->count();
            $this->actingAs($outsider)->post(route('orders.chat.product', [$order, $product]))->assertForbidden();
            $this->post(route('orders.support', $order))->assertForbidden();
            $this->get(route('chats.show', $chat))->assertNotFound();
            $this->get(route('chats.show', $support))->assertNotFound();
            $this->post(route('chats.messages.store', $chat), ['body' => 'Must not be sent'])->assertNotFound();
            self::assertSame($beforeConversations, Conversation::count());
            self::assertSame($beforeMessages, DB::table('messages')->count());
        }
    }

    public function test_admin_role_does_not_gain_buyer_chat_actions(): void
    {
        [$order, $admin, , $product] = $this->fixture('admin');
        $this->actingAs($admin)->post(route('orders.chat.product', [$order, $product]))->assertForbidden();
        $this->post(route('orders.support', $order))->assertForbidden();
    }

    public static function disputeActions(): array
    {
        return [
            ['buyer', 'resolve', OrderDispute::STATUS_RESOLVED],
            ['seller', 'resolve', OrderDispute::STATUS_RESOLVED],
            ['buyer', 'close', OrderDispute::STATUS_CLOSED],
            ['seller', 'close', OrderDispute::STATUS_CLOSED],
        ];
    }

    #[DataProvider('disputeActions')]
    public function test_dispute_notifications_link_to_participant_order_pages(string $role, string $action, string $status): void
    {
        [$order, $buyer, $seller] = $this->fixture($role);
        $admin = User::factory()->create(['role' => 'admin']);
        $outsider = User::factory()->create(['role' => 'seller']);
        $dispute = OrderDispute::create(['order_id' => $order->id, 'user_id' => $buyer->id,
            'seller_id' => $seller->id, 'status' => OrderDispute::STATUS_OPEN, 'reason' => 'Regression dispute']);
        $url = route('admin.disputes.'.$action, $dispute);
        // Reload both snapshots: create() does not populate database-default fields.
        // Sort only keys; retain strict comparison of every stored value and type.
        $before = $dispute->fresh()->getRawOriginal();
        ksort($before);
        $beforeNotifications = UserNotification::count();
        foreach ([$buyer, $seller, $outsider] as $actor) {
            $this->actingAs($actor)->post($url, ['resolution' => 'Not authorized'])->assertForbidden();
            $after = $dispute->fresh()->getRawOriginal();
            ksort($after);
            self::assertSame($before, $after);
        }
        self::assertSame($beforeNotifications, UserNotification::count());
        $this->actingAs($admin)->post($url, ['resolution' => 'Reviewed by administrator'])
            ->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame($status, $dispute->fresh()->status);
        self::assertSame($admin->id, $dispute->fresh()->resolved_by);
        self::assertSame($beforeNotifications + 2, UserNotification::count());

        foreach ([[$buyer, 'orders.show'], [$seller, 'seller.orders.show']] as [$participant, $route]) {
            $notification = UserNotification::where('user_id', $participant->id)
                ->where('type', 'order_dispute_resolved')->where('data->dispute_id', $dispute->id)->sole();
            self::assertSame(route($route, $order, false), $notification->url);
            $this->actingAs($participant)->post(route('notifications.read', $notification))
                ->assertRedirect($notification->url);
            $this->get($notification->url)->assertOk();
            $this->actingAs($outsider)->get($notification->url)->assertForbidden();
            $this->post(route('notifications.read', $notification))->assertForbidden();
        }
    }

    private function fixture(string $buyerRole): array
    {
        $buyer = User::factory()->create(['role' => $buyerRole]);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create(['user_id' => $seller->id, 'title' => 'Participant '.str()->random(12),
            'price' => 100, 'stock' => 3, 'currency_base' => 'MDL', 'status' => Product::STATUS_ACTIVE]);
        $order = Order::create(['user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_COMPLETED,
            'payment_method' => 'cash', 'delivery_method' => 'pickup', 'total_price' => 100, 'currency' => 'MDL']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100, 'total' => 100]);
        return [$order, $buyer, $seller, $product];
    }
}
