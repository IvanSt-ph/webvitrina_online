<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChatAccessRegressionTest extends TestCase
{
    use RefreshDatabase;

    public static function participants(): array
    {
        return [['buyer', 'buyer'], ['seller', 'buyer'], ['seller', 'seller']];
    }

    #[DataProvider('participants')]
    public function test_internal_notes_do_not_affect_search_unread_counts_or_read_state(string $role, string $side): void
    {
        $actor = User::factory()->create(['role' => $role]);
        $other = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->admin()->create();
        $chat = $this->chat($side === 'buyer' ? $actor : $other, $side === 'seller' ? $actor : $other);
        $note = $chat->messages()->create([
            'sender_id' => $admin->id, 'type' => Message::TYPE_INTERNAL_NOTE, 'body' => 'confidential-zebra-934',
        ]);
        $visible = $chat->messages()->create([
            'sender_id' => $other->id, 'type' => Message::TYPE_SYSTEM, 'body' => 'public-lantern-712', 'read_at' => now(),
        ]);
        $this->actingAs($actor);
        foreach (['confidential-zebra-934', 'zebra-934'] as $search) {
            $response = $this->get(route('chats.index', ['q' => $search, 'chat' => $chat->id]))->assertOk();
            $this->assertSame(0, $response->viewData('conversations')->total());
            $this->assertNull($response->viewData('selectedConversation'));
            $this->assertCount(0, $response->viewData('selectedMessages'));
        }
        $response = $this->get(route('chats.index', ['filter' => 'unread']))->assertOk();
        $this->assertSame(0, $response->viewData('conversations')->total());
        $response = $this->get(route('chats.index', ['q' => 'public-lantern-712']))->assertOk();
        $this->assertSame(1, $response->viewData('conversations')->total());
        $this->assertSame(0, $response->viewData('conversations')->first()->unread_count);
        $this->assertSame($visible->id, $response->viewData('conversations')->first()->lastMessage->id);
        $this->get(route('chats.show', $chat))->assertOk()->assertDontSee('confidential-zebra-934');
        $this->getJson(route('chats.messages.newer', [$chat, 'after' => 0]))
            ->assertOk()->assertJsonPath('count', 1)->assertDontSee('confidential-zebra-934');
        $this->assertNull($note->fresh()->read_at);
        $this->assertNull($note->fresh()->admin_read_at);
        $visible->update(['read_at' => null]);
        $this->get(route('chats.index', ['filter' => 'unread']))->assertOk()
            ->assertViewHas('conversations', fn ($page) => $page->total() === 1 && $page->first()->unread_count === 1);
    }

    public function test_cabinet_and_navigation_unread_counts_exclude_notes(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $chat = $this->chat($buyer, $admin, 'support:'.$buyer->id);
        $chat->update(['conversation_type' => Conversation::TYPE_SUPPORT]);
        $chat->messages()->create([
            'sender_id' => $admin->id, 'type' => Message::TYPE_INTERNAL_NOTE, 'body' => 'Hidden support note',
        ]);
        $chat->messages()->create(['sender_id' => $admin->id, 'body' => 'Visible support reply']);
        $navigationCounts = [];
        \Illuminate\Support\Facades\View::composer('layouts.*', function ($view) use (&$navigationCounts): void {
            if (array_key_exists('unreadChatsCount', $view->getData())) {
                $navigationCounts[] = $view->getData()['unreadChatsCount'];
            }
        });
        $this->actingAs($buyer)->get(route('cabinet'))->assertOk()
            ->assertViewHas('unreadMessagesCount', 1)->assertViewHas('supportUnreadCount', 1);
        $this->assertNotEmpty($navigationCounts);
        foreach ($navigationCounts as $count) {
            $this->assertSame(1, $count);
        }
    }

    public function test_search_pagination_totals_and_links_only_include_visible_matches(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->admin()->create();
        for ($i = 0; $i < 22; $i++) {
            $chat = $this->chat($buyer, $seller, 'page-'.$i);
            $chat->messages()->create([
                'sender_id' => $i === 21 ? $admin->id : $seller->id,
                'type' => $i === 21 ? Message::TYPE_INTERNAL_NOTE : Message::TYPE_MESSAGE,
                'body' => 'pagination-needle-953', 'read_at' => now(),
            ]);
        }
        $this->actingAs($buyer);
        $page = $this->get(route('chats.index', ['q' => 'pagination-needle-953', 'filter' => 'general']))
            ->assertOk()->viewData('conversations');
        $this->assertSame(21, $page->total());
        $this->assertCount(20, $page->items());
        parse_str(parse_url($page->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('pagination-needle-953', $query['q']);
        $this->assertSame('general', $query['filter']);
        $second = $this->get($page->nextPageUrl())->assertOk()->viewData('conversations');
        $this->assertSame(21, $second->total());
        $this->assertCount(1, $second->items());
        $this->assertFalse($page->getCollection()->contains('id', $chat->id));
        $this->assertFalse($second->getCollection()->contains('id', $chat->id));
    }

    public function test_hidden_notes_do_not_create_older_message_pages_but_admin_can_search_and_read_them(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->admin()->create();
        $chat = $this->chat($buyer, $seller);
        for ($i = 0; $i < 51; $i++) {
            $chat->messages()->create([
                'sender_id' => $admin->id, 'type' => Message::TYPE_INTERNAL_NOTE, 'body' => 'private-pagination-note',
            ]);
        }
        $visible = $chat->messages()->create(['sender_id' => $seller->id, 'body' => 'Visible message']);
        $this->actingAs($buyer)->get(route('chats.show', $chat))
            ->assertOk()->assertViewHas('hasOlderMessages', false);
        $this->getJson(route('chats.messages.older', [$chat, 'before' => $visible->id + 1]))
            ->assertOk()->assertJsonPath('has_older_messages', false)->assertDontSee('private-pagination-note');
        $this->getJson(route('chats.messages.older', [$chat, 'before' => $visible->id]))
            ->assertOk()->assertJsonPath('oldest_message_id', null)->assertJsonPath('has_older_messages', false);
        $this->actingAs($admin)->get(route('admin.chats.index', [
            'mode' => Conversation::TYPE_MARKETPLACE, 'q' => 'private-pagination-note',
        ]))->assertOk()->assertViewHas('conversations', fn ($page) => $page->total() === 1
            && $page->first()->internal_notes_count === 51);
        $this->get(route('admin.chats.show', $chat))->assertOk()->assertSee('private-pagination-note');
    }

    public function test_seller_pins_only_their_own_side_and_mixed_inbox_uses_per_chat_participation(): void
    {
        $actor = User::factory()->create(['role' => 'seller']);
        $shopOwner = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $buying = $this->chat($actor, $shopOwner, 'buying');
        $selling = $this->chat($buyer, $actor, 'selling');
        $otherPin = $this->chat($actor, $shopOwner, 'other-pin');
        $otherPin->update(['seller_pinned_at' => now(), 'last_message_at' => now()->addDay()]);
        $buying->update(['seller_pinned_at' => now()->subDay()]);
        $selling->update(['buyer_pinned_at' => now()->subDay()]);
        $otherSellerPin = $buying->fresh()->getRawOriginal('seller_pinned_at');
        $otherBuyerPin = $selling->fresh()->getRawOriginal('buyer_pinned_at');
        $this->actingAs($actor);
        foreach ([$buying, $selling] as $chat) {
            $this->post(route('chats.pin', $chat))->assertRedirect();
        }
        $this->assertNotNull($buying->fresh()->buyer_pinned_at);
        $this->assertNotNull($selling->fresh()->seller_pinned_at);
        $page = $this->get(route('chats.index', ['filter' => 'pinned']))->assertOk()->viewData('conversations');
        $this->assertEqualsCanonicalizing([$buying->id, $selling->id], $page->pluck('id')->all());
        $response = $this->get(route('chats.index'))->assertOk();
        $this->assertSame($otherPin->id, $response->viewData('conversations')->last()->id);
        $this->assertSame(2, substr_count($response->getContent(), 'ri-pushpin-fill'));
        foreach ([$buying, $selling] as $chat) {
            $this->post(route('chats.pin', $chat))->assertRedirect();
        }
        $this->assertNull($buying->fresh()->buyer_pinned_at);
        $this->assertNull($selling->fresh()->seller_pinned_at);
        $this->assertSame($otherSellerPin, $buying->fresh()->getRawOriginal('seller_pinned_at'));
        $this->assertSame($otherBuyerPin, $selling->fresh()->getRawOriginal('buyer_pinned_at'));
        $this->get(route('chats.index', ['filter' => 'pinned']))->assertOk()
            ->assertViewHas('conversations', fn ($page) => $page->total() === 0);
    }

    public function test_seller_support_pin_uses_buyer_side_and_outsiders_and_hidden_chats_stay_forbidden(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->create(['role' => 'seller']);
        $chat = $this->chat($seller, $admin, 'support:'.$seller->id);
        $chat->update(['conversation_type' => Conversation::TYPE_SUPPORT]);
        $this->actingAs($seller)->post(route('chats.pin', $chat))->assertRedirect();
        $this->assertNotNull($chat->fresh()->buyer_pinned_at);
        $this->assertNull($chat->fresh()->seller_pinned_at);
        $this->get(route('chats.index', ['filter' => 'pinned']))->assertOk()
            ->assertViewHas('conversations', fn ($page) => $page->total() === 1);
        $this->actingAs($outsider)->post(route('chats.pin', $chat))->assertNotFound();
        $this->assertNull($chat->pinnedColumnFor($outsider));
        $chat->update(['buyer_deleted_at' => now()]);
        $this->actingAs($seller)->post(route('chats.pin', $chat))->assertNotFound();
        $this->get(route('chats.index', ['filter' => 'pinned']))->assertOk()
            ->assertViewHas('conversations', fn ($page) => $page->total() === 0);
        $this->assertNotNull($chat->fresh()->buyer_pinned_at);
    }

    private function chat(User $buyer, User $seller, string $context = 'general'): Conversation
    {
        return Conversation::create([
            'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'context_key' => $context,
            'conversation_type' => Conversation::TYPE_MARKETPLACE, 'last_message_at' => now(),
        ]);
    }
}
