<?php

namespace Tests\Feature;

use App\Models\{Conversation, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderSellerChatSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'HistoricalSeller']);
        $shop = $seller->shop()->create(['name' => 'HistoricalShop']);
        $order = Order::create(['user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => 'CHAT-'.uniqid(), 'status' => 'pending', 'total_price' => 100, 'currency' => 'PRB']);
        $chat = Conversation::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'order_id' => $order->id, 'context_key' => 'order:'.$order->id,
            'conversation_type' => Conversation::TYPE_MARKETPLACE]);
        $shop->update(['name' => 'CurrentShop']);
        $seller->update(['name' => 'CurrentSeller']);
        $admin = User::factory()->create(['role' => 'admin']);
        return [$buyer, $seller, $admin, $order, $chat];
    }

    public function test_support_and_admin_views_keep_historical_seller_separate_from_current_contacts(): void
    {
        [$buyer, , $admin, $order, $chat] = $this->context();
        // Compare two persisted JSON values: MySQL normalizes object key order.
        $original = $order->fresh()->seller_snapshot;
        $this->actingAs($admin)->post(route('admin.chats.support.start', $buyer),
            ['source_conversation_id' => $chat->id])->assertRedirect();
        $body = DB::table('messages')->where('related_conversation_id', $chat->id)->value('body');
        $this->assertStringContainsString('Продавец на момент заказа: HistoricalShop / HistoricalSeller', $body);
        $this->assertStringNotContainsString('CurrentShop', $body);
        foreach ([route('admin.chats.index', ['mode' => 'marketplace']), route('admin.chats.show', $chat)] as $url) {
            $this->get($url)->assertOk()->assertSee('Продавец на момент заказа: HistoricalShop / HistoricalSeller')
                ->assertSee('CurrentShop')
                ->assertViewHas('conversations', fn ($rows) => $rows->every(fn ($row) => $row->relationLoaded('order')));
        }
        $this->assertSame($original, $order->fresh()->seller_snapshot);
    }

    public function test_historical_and_current_search_preserves_participant_boundaries_and_regular_chats(): void
    {
        [$buyer, $seller, $admin, , $chat] = $this->context();
        $regular = Conversation::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'conversation_type' => Conversation::TYPE_MARKETPLACE]);
        foreach ([[$buyer, 'chats.index'], [$seller, 'chats.index'], [$admin, 'admin.chats.index']] as [$user, $route]) {
            foreach (['HistoricalShop', 'HistoricalSeller'] as $search) {
                $this->actingAs($user)->get(route($route, ['q' => $search, 'mode' => 'marketplace']))->assertOk()
                    ->assertViewHas('conversations', fn ($rows) => $rows->pluck('id')->all() === [$chat->id]);
            }
            foreach (['CurrentShop', 'CurrentSeller'] as $search) {
                $this->get(route($route, ['q' => $search, 'mode' => 'marketplace']))->assertOk()
                    ->assertViewHas('conversations', fn ($rows) => $rows->pluck('id')->sort()->values()->all()
                        === collect([$chat->id, $regular->id])->sort()->values()->all());
            }
        }
        $outsider = User::factory()->create(['role' => 'buyer']);
        foreach (['HistoricalShop', 'HistoricalSeller', 'CurrentShop'] as $search) {
            $this->actingAs($outsider)->get(route('chats.index', ['q' => $search]))->assertOk()
                ->assertViewHas('conversations', fn ($rows) => $rows->isEmpty());
        }
        $this->get(route('chats.show', $chat))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.chats.support.start', $buyer),
            ['source_conversation_id' => $regular->id])->assertRedirect();
        $this->assertStringContainsString('Продавец: CurrentShop',
            DB::table('messages')->where('related_conversation_id', $regular->id)->value('body'));
        $this->assertNull($regular->historical_seller_label);
    }

    public function test_missing_and_partial_snapshots_have_safe_fallbacks(): void
    {
        [$buyer, , $admin, $order, $chat] = $this->context();
        foreach ([
            [null, 'Сведения не сохранены'],
            [[], 'Сведения не сохранены'],
            [['source' => 'checkout'], 'Сведения не сохранены'],
            [['name' => 'OnlySeller', 'source' => 'checkout'], 'OnlySeller'],
            [['shop_name' => 'OnlyShop', 'source' => 'checkout'], 'OnlyShop'],
            [['shop_name' => 'LegacyShop'], 'LegacyShop (данные на момент покупки не подтверждены)'],
        ] as [$snapshot, $expected]) {
            // Test-only legacy fixtures; application writes never modify snapshots.
            DB::table('orders')->where('id', $order->id)->update([
                'seller_snapshot' => $snapshot === null ? null : json_encode($snapshot),
            ]);
            $this->actingAs($admin)->get(route('admin.chats.show', $chat))->assertOk();
            $this->post(route('admin.chats.support.start', $buyer),
                ['source_conversation_id' => $chat->id])->assertRedirect();
            $body = DB::table('messages')->where('related_conversation_id', $chat->id)->orderByDesc('id')->value('body');
            $this->assertStringContainsString('Продавец на момент заказа: '.$expected, $body);
            $this->assertStringNotContainsString('CurrentShop', $body);
        }
    }
}
