<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Message;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\AdminActivityLog;
use App\Models\SellerPlanRequest;
use App\Models\User;
use App\Models\UserAddress;
use App\Repositories\ProductCrudRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_numbers_are_unique_and_keep_public_prefix(): void
    {
        $first = Order::generateNumber();
        $second = Order::generateNumber();

        $this->assertStringStartsWith('ORD-', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_non_admin_cannot_update_order_status_through_admin_route(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-TEST-1',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'RUB',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('admin.orders.updateStatus', $order), ['status' => Order::STATUS_PAID])
            ->assertForbidden();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_admin_orders_index_has_operational_filters_and_product_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Admin Order Buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Admin Order Seller']);
        $seller->shop()->create(['name' => 'Admin order shop']);
        $product = $this->createProduct($seller, [
            'title' => 'Rare admin searchable product',
            'sku' => 'ADMIN-ORDER-SKU',
        ]);

        $matchingOrder = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-ADMIN-FILTER',
            'status' => Order::STATUS_PENDING,
            'total_price' => 450,
            'currency' => 'MDL',
            'payment_method' => 'card',
            'delivery_method' => 'courier',
        ]);

        OrderItem::create([
            'order_id' => $matchingOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 225,
            'total' => 450,
        ]);

        $hiddenOrder = $this->createOrder($buyer, $seller, Order::STATUS_CANCELED);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', [
                'q' => 'ADMIN-ORDER-SKU',
                'status' => Order::STATUS_PENDING,
                'sort' => 'amount_desc',
            ]))
            ->assertOk()
            ->assertSee('Требуют внимания')
            ->assertSee('Сумма по фильтру')
            ->assertSee('Rare admin searchable product')
            ->assertSee('ORD-ADMIN-FILTER')
            ->assertSee('date_from', false)
            ->assertSee('amount_desc', false)
            ->assertDontSee($hiddenOrder->number);
    }

    public function test_admin_order_detail_requires_cancel_reason_and_logs_status_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Order detail buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Order detail seller']);
        $order = $this->createOrder($buyer, $seller);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Решение по заказу')
            ->assertSee('Диалоги по ситуации')
            ->assertSee('Order detail buyer');

        $this->actingAs($admin)
            ->post(route('admin.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED,
            ])
            ->assertSessionHasErrors('change_reason');

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED,
                'change_reason' => 'Подтверждена отмена после обращения покупателя.',
            ])
            ->assertRedirect();

        $this->assertSame(Order::STATUS_CANCELED, $order->fresh()->status);
        $log = AdminActivityLog::where('action', 'order.status_updated')->firstOrFail();
        $this->assertSame('Подтверждена отмена после обращения покупателя.', $log->meta['reason']);
    }

    public function test_quick_checkout_rejects_invalid_quantity(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('checkout.quick', $product), ['qty' => -100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');
    }

    public function test_checkout_rejects_unknown_payment_and_delivery_methods(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 1,
                    'image' => $product->image,
                ]],
            ])
            ->postJson(route('checkout.create'), [
                'payment_method' => 'lol',
                'delivery_method' => 'free_delivery',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method', 'delivery_method']);
    }

    public function test_checkout_requires_address_for_delivery_methods_that_need_one(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 1,
                    'image' => $product->image,
                ]],
            ])
            ->postJson(route('checkout.create'), [
                'payment_method' => 'cash',
                'delivery_method' => 'courier',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');

        $this->assertDatabaseMissing('orders', [
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
    }

    public function test_checkout_confirm_uses_mobile_safe_layout_for_long_titles(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'КроссовкиTommyHilfigerEM0EM01527BDSбелыессинейподошвойдлинноеназваниебезпробелов',
        ]);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 1,
                    'image' => $product->image,
                ]],
            ])
            ->get(route('checkout.confirm'))
            ->assertOk()
            ->assertSee('checkout-confirm-safe', false)
            ->assertSee('min-w-0', false)
            ->assertSee('КроссовкиTommyHilf...', false);
    }

    public function test_buyer_cannot_create_or_update_shop_profile(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Buyer shop',
                'city' => 'Test city',
                'description' => 'Should not be created',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('shops', [
            'user_id' => $buyer->id,
            'name' => 'Buyer shop',
        ]);
    }

    public function test_shop_slug_is_generated_when_shop_is_created(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Мой тестовый магазин',
        ]);

        $this->assertNotNull($shop->slug);
        $this->assertNotSame('', $shop->slug);
        $this->assertSame($shop->slug, $shop->fresh()->slug);
    }

    public function test_buyer_can_start_chat_with_seller_and_send_message(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Chat seller']);

        $this->actingAs($buyer)
            ->post(route('chats.start', $shop))
            ->assertRedirect();

        $conversation = Conversation::firstOrFail();

        $this->actingAs($buyer)
            ->post(route('chats.messages.store', $conversation), ['body' => 'Здравствуйте!'])
            ->assertRedirect(route('chats.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'Здравствуйте!',
        ]);
    }

    public function test_buyer_can_start_order_product_chat_with_visible_product_context(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['title' => 'Purchased chat product']);
        $order = $this->createOrder($buyer, $seller);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($otherBuyer)
            ->post(route('orders.chat.product', [$order, $product]))
            ->assertForbidden();

        $this->actingAs($buyer)
            ->post(route('orders.chat.product', [$order, $product]))
            ->assertRedirect();

        $conversation = Conversation::where('product_id', $product->id)->firstOrFail();
        $this->assertSame(Conversation::orderProductContextKey($order, $product), $conversation->context_key);
        $this->assertSame($order->id, $conversation->order_id);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'type' => Message::TYPE_SYSTEM,
            'body' => "Диалог по заказу {$order->number}.\nТовар: Purchased chat product",
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Заказ ' . $order->number)
            ->assertSee('Purchased chat product')
            ->assertSee(route('product.show', $product->slug), false)
            ->assertSee($order->number);
    }

    public function test_buyer_opens_support_from_order_with_order_context(): void
    {
        User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Support order shop']);
        $product = $this->createProduct($seller, ['title' => 'Support context product']);
        $order = $this->createOrder($buyer, $seller);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->post(route('orders.support', $order))
            ->assertRedirect();

        $conversation = Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)
            ->where('buyer_id', $buyer->id)
            ->firstOrFail();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'type' => Message::TYPE_SYSTEM,
            'order_id' => $order->id,
            'body' => "Обращение по заказу {$order->number}.\n"
                . "Магазин: Support order shop\n"
                . "Товары: Support context product\n"
                . 'Опишите проблему следующим сообщением.',
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Обращение по заказу')
            ->assertSee($order->number)
            ->assertSee(route('orders.show', $order), false)
            ->assertSee(route('product.show', $product->slug), false);
    }

    public function test_starting_chat_from_seller_page_opens_widget_mode(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Widget seller']);

        $response = $this->actingAs($buyer)
            ->post(route('chats.start', $shop));

        $conversation = Conversation::firstOrFail();

        $response->assertRedirect(route('seller.show', [
            'identifier' => $shop->slug,
            'chat' => $conversation->id,
        ]));

        $this->actingAs($buyer)
            ->get(route('seller.show', [
            'identifier' => $shop->slug,
            'chat' => $conversation->id,
        ]))
            ->assertOk()
            ->assertSee('Поздороваться')
            ->assertSee('value="' . route('seller.show', [
                'identifier' => $shop->slug,
                'chat' => $conversation->id,
            ], false) . '"', false);
    }

    public function test_seller_page_rejects_chat_from_another_seller_context(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Correct seller']);
        $otherShop = $otherSeller->shop()->create(['name' => 'Other seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => Conversation::generalContextKey(),
        ]);

        $this->actingAs($buyer)
            ->get(route('seller.show', [
                'identifier' => $otherShop->slug,
                'chat' => $conversation->id,
            ]))
            ->assertNotFound();

        $this->actingAs($buyer)
            ->get(route('seller.show', [
                'identifier' => $shop->slug,
                'chat' => $conversation->id,
            ]))
            ->assertOk();
    }

    public function test_starting_chat_from_product_page_opens_product_context_widget(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Product seller']);
        $product = $this->createProduct($seller, ['title' => 'Product chat context']);

        $response = $this->actingAs($buyer)
            ->post(route('chats.product.start', $product->slug));

        $conversation = Conversation::firstOrFail();

        $this->assertSame($product->id, $conversation->product_id);

        $response->assertRedirect(route('product.show', [
            'identifier' => $product->slug,
            'chat' => $conversation->id,
        ]));

        $this->actingAs($buyer)
            ->get(route('product.show', [
                'identifier' => $product->slug,
                'chat' => $conversation->id,
            ]))
            ->assertOk()
            ->assertSee('Диалог по товару')
            ->assertSee('Product chat context')
            ->assertSee('Есть в наличии?');
    }

    public function test_chat_creation_reuses_general_and_product_contexts_without_duplicates(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Unique seller']);
        $product = $this->createProduct($seller, ['title' => 'Unique chat product']);

        $this->actingAs($buyer)->post(route('chats.start', $shop));
        $this->actingAs($buyer)->post(route('chats.start', $shop));
        $this->actingAs($buyer)->post(route('chats.product.start', $product->slug));
        $this->actingAs($buyer)->post(route('chats.product.start', $product->slug));

        $this->assertDatabaseCount('conversations', 2);
        $this->assertDatabaseHas('conversations', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'general',
        ]);
        $this->assertDatabaseHas('conversations', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'context_key' => 'product:' . $product->id,
        ]);
    }

    public function test_seller_can_start_product_chat_with_another_seller(): void
    {
        $sellerBuyer = User::factory()->create(['role' => 'seller']);
        $sellerOwner = User::factory()->create(['role' => 'seller']);
        $sellerOwner->shop()->create(['name' => 'Other seller']);
        $product = $this->createProduct($sellerOwner, ['title' => 'Seller to seller product']);

        $this->actingAs($sellerBuyer)
            ->post(route('chats.product.start', $product->slug))
            ->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'buyer_id' => $sellerBuyer->id,
            'seller_id' => $sellerOwner->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_chat_is_visible_only_to_participants(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $outsider = User::factory()->create(['role' => 'buyer']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('chats.show', $conversation))
            ->assertNotFound();

        $this->actingAs($outsider)
            ->post(route('chats.messages.store', $conversation), ['body' => 'Чужое сообщение'])
            ->assertNotFound();

        $this->actingAs($outsider)
            ->getJson(route('chats.messages.older', [
                'conversation' => $conversation,
                'before' => 1,
            ]))
            ->assertNotFound();
    }

    public function test_chat_page_renders_for_participant(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Начните разговор')
            ->assertSee('h-dvh', false)
            ->assertDontSee('data-mobile-bottom-nav', false);
    }

    public function test_chat_message_bodies_are_escaped_in_user_and_admin_views(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $payload = '<script>alert(1)</script>';

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'last_message_at' => now(),
        ]);

        $messageResponse = $this->actingAs($buyer)
            ->postJson(route('chats.messages.store', $conversation), [
                'body' => $payload,
            ])
            ->assertCreated();

        $this->assertStringContainsString(e($payload), $messageResponse->json('html'));
        $this->assertStringNotContainsString($payload, $messageResponse->json('html'));
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => $payload,
        ]);

        $noteResponse = $this->actingAs($admin)
            ->postJson(route('admin.chats.note', $conversation), [
                'body' => $payload,
            ])
            ->assertCreated();

        $this->assertStringContainsString(e($payload), $noteResponse->json('html'));
        $this->assertStringNotContainsString($payload, $noteResponse->json('html'));

        $systemResponse = $this->actingAs($admin)
            ->postJson(route('admin.chats.system', $conversation), [
                'body' => $payload,
            ])
            ->assertCreated();

        $this->assertStringContainsString(e($payload), $systemResponse->json('html'));
        $this->assertStringNotContainsString($payload, $systemResponse->json('html'));

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false);

        $this->actingAs($seller)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false);

        $supportConversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:' . $buyer->id,
            'last_message_at' => now(),
        ]);

        $supportResponse = $this->actingAs($admin)
            ->postJson(route('admin.chats.messages.store', $supportConversation), [
                'body' => $payload,
            ])
            ->assertCreated();

        $this->assertStringContainsString(e($payload), $supportResponse->json('html'));
        $this->assertStringNotContainsString($payload, $supportResponse->json('html'));

        $this->actingAs($buyer)
            ->get(route('chats.show', $supportConversation))
            ->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $supportConversation))
            ->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false);
    }

    public function test_chat_page_loads_only_latest_fifty_messages(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'general',
        ]);

        foreach (range(1, 51) as $number) {
            $conversation->messages()->create([
                'sender_id' => $buyer->id,
                'body' => $number === 1 ? 'Oldest hidden message' : 'Message ' . $number,
            ]);
        }

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Показать предыдущие сообщения')
            ->assertDontSee('Oldest hidden message')
            ->assertSee('Message 51');
    }

    public function test_chat_can_load_older_messages_in_batches(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'general',
        ]);

        foreach (range(1, 125) as $number) {
            $conversation->messages()->create([
                'sender_id' => $buyer->id,
                'body' => 'Message ' . $number,
            ]);
        }

        $oldestVisibleId = $conversation->messages()->orderByDesc('id')->skip(49)->value('id');

        $firstBatch = $this->actingAs($buyer)
            ->getJson(route('chats.messages.older', [
                'conversation' => $conversation,
                'before' => $oldestVisibleId,
            ]))
            ->assertOk()
            ->assertJson([
                'has_older_messages' => true,
            ])
            ->assertJsonPath('oldest_message_id', fn ($id) => is_int($id) && $id > 0);

        $this->assertStringContainsString('Message 26', $firstBatch->json('html'));
        $this->assertStringContainsString('Message 75', $firstBatch->json('html'));
        $this->assertStringNotContainsString('Message 76', $firstBatch->json('html'));

        $secondBatch = $this->actingAs($buyer)
            ->getJson(route('chats.messages.older', [
                'conversation' => $conversation,
                'before' => $firstBatch->json('oldest_message_id'),
            ]))
            ->assertOk()
            ->assertJson([
                'has_older_messages' => false,
            ]);

        $this->assertStringContainsString('Message 1', $secondBatch->json('html'));
        $this->assertStringNotContainsString('Message 26', $secondBatch->json('html'));
    }

    public function test_chat_can_fetch_newer_messages_after_latest_visible_id(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $first = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Первое сообщение',
        ]);

        $second = $conversation->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Новое сообщение продавца',
        ]);

        $response = $this->actingAs($buyer)
            ->getJson(route('chats.messages.newer', [
                'conversation' => $conversation,
                'after' => $first->id,
            ]))
            ->assertOk()
            ->assertJson([
                'latest_message_id' => $second->id,
                'count' => 1,
            ]);

        $this->assertStringContainsString('Новое сообщение продавца', $response->json('html'));

        $this->assertNotNull($second->fresh()->read_at);
    }

    public function test_chat_newer_messages_response_includes_latest_read_outgoing_message_id(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $sent = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Моё сообщение',
            'read_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->getJson(route('chats.messages.newer', [
                'conversation' => $conversation,
                'after' => 0,
            ]))
            ->assertOk()
            ->assertJson([
                'latest_read_outgoing_message_id' => $sent->id,
            ]);
    }

    public function test_chat_page_shows_read_indicator_for_own_read_message(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Прочитанное сообщение',
            'read_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('wv-read-status is-read', false)
            ->assertSee('title="Прочитано"', false);
    }

    public function test_chat_can_send_message_as_json_without_page_reload(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $response = $this->actingAs($buyer)
            ->postJson(route('chats.messages.store', $conversation), [
                'body' => 'Сообщение без перезагрузки',
            ])
            ->assertCreated()
            ->assertJsonPath('latest_message_id', fn ($id) => is_int($id) && $id > 0);

        $this->assertStringContainsString('Сообщение без перезагрузки', $response->json('html'));
    }

    public function test_chat_can_send_private_optimized_image_message(): void
    {
        Storage::fake('local');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->post(route('chats.messages.store', $conversation), [
                'image' => UploadedFile::fake()->image('chat.png', 320, 240),
            ])
            ->assertRedirect(route('chats.show', $conversation));

        $message = Message::firstOrFail();

        $this->assertSame('', $message->body);
        $this->assertNotNull($message->image_path);
        $this->assertStringEndsWith('.webp', $message->image_path);
        Storage::disk('local')->assertExists($message->image_path);

        $this->actingAs($buyer)
            ->get(route('chats.messages.image', [$conversation, $message]))
            ->assertOk()
            ->assertHeader('content-type', 'image/webp')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_chat_rejects_svg_image_upload(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->from(route('chats.show', $conversation))
            ->post(route('chats.messages.store', $conversation), [
                'image' => UploadedFile::fake()->create('chat.svg', 1, 'image/svg+xml'),
            ])
            ->assertRedirect(route('chats.show', $conversation))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_chat_rejects_oversized_image_dimensions(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->from(route('chats.show', $conversation))
            ->post(route('chats.messages.store', $conversation), [
                'image' => UploadedFile::fake()->image('huge.png', 8001, 100),
            ])
            ->assertRedirect(route('chats.show', $conversation))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_chat_rejects_external_redirect_target_after_sending_message(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($buyer)
            ->post(route('chats.messages.store', $conversation), [
                'body' => 'Проверка редиректа',
                'redirect_to' => config('app.url') . '.evil.example/phishing',
            ])
            ->assertRedirect(route('chats.show', $conversation));
    }

    public function test_chat_image_is_visible_only_to_participants(): void
    {
        Storage::fake('local');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $outsider = User::factory()->create(['role' => 'buyer']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        Storage::disk('local')->put('chat-images/test.webp', 'private image');
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => '',
            'image_path' => 'chat-images/test.webp',
        ]);

        $this->actingAs($outsider)
            ->get(route('chats.messages.image', [$conversation, $message]))
            ->assertNotFound();
    }

    public function test_chat_image_file_is_removed_when_message_is_deleted(): void
    {
        Storage::fake('local');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        Storage::disk('local')->put('chat-images/delete-me.webp', 'private image');
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => '',
            'image_path' => 'chat-images/delete-me.webp',
        ]);

        $message->delete();

        Storage::disk('local')->assertMissing('chat-images/delete-me.webp');
    }

    public function test_chat_image_files_are_removed_when_conversation_is_deleted(): void
    {
        Storage::fake('local');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        Storage::disk('local')->put('chat-images/delete-with-conversation.webp', 'private image');
        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => '',
            'image_path' => 'chat-images/delete-with-conversation.webp',
        ]);

        $conversation->delete();

        Storage::disk('local')->assertMissing('chat-images/delete-with-conversation.webp');
    }

    public function test_chat_list_shows_product_context_and_general_label(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'List seller']);
        $product = $this->createProduct($seller, ['title' => 'List context product']);

        Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.index'))
            ->assertOk()
            ->assertSee('Общий диалог')
            ->assertSee('List context product')
            ->assertDontSee('По товару: List context product')
            ->assertSee(route('product.show', $product->slug), false)
            ->assertSee(route('seller.show', $shop->slug), false)
            ->assertSee('aria-label="Открыть чат"', false)
            ->assertSee('aria-label="Показать чат справа"', false);
    }

    public function test_chat_lists_prioritize_unread_then_recent_dialogs(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $readRecent = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'sort:read',
            'last_message_at' => now(),
        ]);
        $unreadOld = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'sort:old',
            'last_message_at' => now()->subDays(2),
        ]);
        $unreadFresh = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'sort:fresh',
            'last_message_at' => now()->subDay(),
        ]);

        $readRecent->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Read newest dialog',
            'read_at' => now(),
        ]);
        $unreadOld->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Unread older dialog',
        ]);
        $unreadFresh->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Unread fresher dialog',
        ]);

        $content = $this->actingAs($buyer)
            ->get(route('chats.index'))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($content, 'Unread older dialog'),
            strpos($content, 'Unread fresher dialog')
        );
        $this->assertLessThan(
            strpos($content, 'Read newest dialog'),
            strpos($content, 'Unread older dialog')
        );
    }

    public function test_chat_index_search_filters_and_pin_dialogs(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Search Seller']);
        $seller->shop()->create(['name' => 'Searchable Shop']);
        $product = $this->createProduct($seller, ['title' => 'Findable Chat Product']);
        $matching = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'context_key' => Conversation::productContextKey($product),
            'last_message_at' => now()->subDay(),
        ]);
        $other = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'chat-search-other',
            'last_message_at' => now(),
        ]);
        $matching->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Needle message body',
        ]);
        $other->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Other dialog body',
            'read_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.index', ['q' => 'Needle']))
            ->assertOk()
            ->assertSee('Поиск и фильтры')
            ->assertSee('<details', false)
            ->assertSee('активно')
            ->assertSee('Needle message body')
            ->assertDontSee('Other dialog body');

        $this->actingAs($buyer)
            ->get(route('chats.index', ['filter' => 'products']))
            ->assertOk()
            ->assertSee('Findable Chat Product')
            ->assertDontSee('Other dialog body');

        $this->actingAs($buyer)
            ->post(route('chats.pin', $matching))
            ->assertRedirect();

        $this->assertNotNull($matching->fresh()->buyer_pinned_at);
        $this->assertNull($matching->fresh()->seller_pinned_at);

        $this->actingAs($buyer)
            ->get(route('chats.index', ['filter' => 'pinned']))
            ->assertOk()
            ->assertSee('Findable Chat Product')
            ->assertSee('ri-pushpin-fill')
            ->assertDontSee('Other dialog body');

        $this->actingAs($seller)
            ->post(route('chats.pin', $matching))
            ->assertRedirect();

        $this->assertNotNull($matching->fresh()->seller_pinned_at);

        $this->actingAs($seller)
            ->get(route('chats.index', ['filter' => 'pinned']))
            ->assertOk()
            ->assertSee('Findable Chat Product');
    }

    public function test_chat_index_can_select_conversation_inline_on_desktop(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => Conversation::generalContextKey(),
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $seller->id,
            'body' => 'Inline desktop chat body',
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.index', ['chat' => $conversation->id]))
            ->assertOk()
            ->assertSee('flex h-dvh', false)
            ->assertSee('overflow-y-auto rounded-2xl', false)
            ->assertSee(route('chats.index', ['chat' => $conversation->id]), false)
            ->assertSee(route('chats.show', $conversation), false)
            ->assertSee('Inline desktop chat body');
    }

    public function test_admin_can_moderate_marketplace_chat_without_joining_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Admin Chat Buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Admin Chat Seller']);
        $seller->shop()->create(['name' => 'Admin Chat Shop']);
        $product = $this->createProduct($seller, ['title' => 'Admin chat product']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'context_key' => Conversation::productContextKey($product),
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Нужна помощь администратора',
        ]);
        $conversation->messages()->create([
            'sender_id' => $admin->id,
            'type' => Message::TYPE_INTERNAL_NOTE,
            'body' => 'Внутренняя заметка для списка',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('Marketplace: только модерация')
            ->assertSee('Admin Chat Buyer')
            ->assertSee('Admin Chat Shop')
            ->assertSee('Admin chat product')
            ->assertSee('Нужна помощь администратора')
            ->assertSee('Покупатель')
            ->assertSee('Это диалог покупателя и продавца')
            ->assertSee('Модерация чата')
            ->assertSee('fixed inset-0 z-[70]', false)
            ->assertDontSee('window.innerWidth', false)
            ->assertDontSee('Чат #' . $conversation->id)
            ->assertSee('h-[100dvh]', false)
            ->assertSee('mobileListOpen', false)
            ->assertSee('adminChatListWidth', false)
            ->assertSee('gridTemplateColumns', false)
            ->assertSee('cursor-col-resize', false)
            ->assertSee('lg:h-full', false)
            ->assertSee('x-ref="messages" class="w-full', false)
            ->assertSee(route('product.show', [
                'identifier' => $product->slug,
                'admin_chat' => $conversation->id,
            ]), false)
            ->assertSee('ID ' . $conversation->id)
            ->assertSee('Заметки 1')
            ->assertDontSee('>' . $conversation->messages()->count() . '</span>', false)
            ->assertSee('max-lg:hidden', false)
            ->assertSee(route('admin.users.show', $buyer), false)
            ->assertSee(route('seller.show', $seller->shop->slug), false)
            ->assertSee(e($buyer->avatar_url), false);

        $this->actingAs($admin)
            ->postJson(route('admin.chats.messages.store', $conversation), [
                'body' => 'Поддержка подключилась к диалогу.',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.chats.system', $conversation), [
                'body' => 'Поддержка проверяет этот диалог.',
            ])
            ->assertRedirect(route('admin.chats.show', $conversation));

        $this->actingAs($admin)
            ->post(route('admin.chats.lock', $conversation), [
                'reason' => 'Проверка спорной ситуации',
            ])
            ->assertRedirect(route('admin.chats.show', $conversation));

        $this->assertTrue($conversation->fresh()->isLocked());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'type' => 'system',
            'body' => 'Поддержка проверяет этот диалог.',
        ]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'type' => 'system',
            'body' => 'Диалог временно заблокирован поддержкой. Причина: Проверка спорной ситуации',
        ]);

        $this->actingAs($buyer)
            ->post(route('chats.messages.store', $conversation), ['body' => 'Почему не отправляется?'])
            ->assertStatus(423);
    }

    public function test_admin_public_product_page_does_not_show_buyer_mobile_nav(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Admin public product shop']);
        $product = $this->createProduct($seller, ['title' => 'Admin public product']);

        $this->actingAs($admin)
            ->get(route('product.show', [
                'identifier' => $product->slug,
                'admin_chat' => 123,
            ]))
            ->assertOk()
            ->assertSee('Админ-просмотр товара')
            ->assertSee('Карточка открыта из диалога ID 123')
            ->assertSee(route('admin.chats.show', 123), false)
            ->assertSee(route('admin.products.edit', $product), false)
            ->assertDontSee('data-mobile-bottom-nav', false)
            ->assertDontSee('data-mobile-bottom-seller-nav', false);
    }

    public function test_admin_support_chat_is_separate_from_marketplace_chat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Support Buyer']);

        $this->actingAs($admin)
            ->post(route('admin.chats.support.start', $buyer))
            ->assertRedirect();

        $conversation = Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)->firstOrFail();

        $this->assertSame($buyer->id, $conversation->buyer_id);
        $this->assertSame($admin->id, $conversation->seller_id);
        $this->assertSame('support:' . $buyer->id, $conversation->context_key);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.chats.messages.store', $conversation), [
                'body' => 'Здравствуйте, это поддержка WebVitrina.',
            ])
            ->assertCreated()
            ->assertJsonPath('latest_message_id', fn ($id) => is_int($id) && $id > 0);

        $this->assertStringContainsString('Здравствуйте, это поддержка WebVitrina.', $response->json('html'));
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'body' => 'Здравствуйте, это поддержка WebVitrina.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('Здравствуйте! Уточните', false)
            ->assertSee(route('admin.chats.note', $conversation), false);
    }

    public function test_users_can_open_support_chat_from_support_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Seller Needs Support']);
        $seller->shop()->create(['name' => 'Seller Support Shop']);

        $this->actingAs($seller)
            ->get(route('support'))
            ->assertOk()
            ->assertSee('support-mobile-safe', false)
            ->assertSee('Открыть обращение')
            ->assertSee('Безопасность')
            ->assertSee(route('support.start'), false);

        $this->actingAs($seller)
            ->post(route('support.start'))
            ->assertRedirect();

        $conversation = Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)
            ->where('buyer_id', $seller->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $conversation->seller_id);
        $this->assertSame('support:' . $seller->id, $conversation->context_key);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'type' => Message::TYPE_SYSTEM,
            'body' => 'Support-чат открыт. Опишите вопрос, спор или проблему — поддержка ответит здесь.',
        ]);

        $this->actingAs($seller)
            ->get(route('support'))
            ->assertOk()
            ->assertSee('Продолжить support-чат')
            ->assertSee(route('chats.show', $conversation), false);
    }

    public function test_support_page_can_send_topic_context_to_admin(): void
    {
        User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->post(route('support.start'), [
                'topic' => 'Проблема с товаром',
                'details' => 'Нужна помощь с карточкой товара.',
            ])
            ->assertRedirect();

        $conversation = Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)
            ->where('buyer_id', $seller->id)
            ->firstOrFail();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $seller->id,
            'type' => Message::TYPE_SYSTEM,
            'body' => "Новое обращение в поддержку.\nТема: Проблема с товаром\nПодробности: Нужна помощь с карточкой товара.",
        ]);
    }

    public function test_user_can_open_support_dispute_from_marketplace_chat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Dispute Buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Dispute Seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'last_message_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Пожаловаться')
            ->assertSee('Обращение в поддержку')
            ->assertSee(route('chats.support.dispute', $conversation), false);

        $this->actingAs($buyer)
            ->post(route('chats.support.dispute', $conversation), [
                'reason' => 'Подозрение на мошенничество',
                'details' => '<script>alert(1)</script>',
            ])
            ->assertRedirect();

        $supportConversation = Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)
            ->where('buyer_id', $buyer->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $supportConversation->seller_id);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $supportConversation->id,
            'sender_id' => $buyer->id,
            'type' => Message::TYPE_SYSTEM,
            'related_conversation_id' => $conversation->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('chats.show', $supportConversation))
            ->assertOk()
            ->assertSee('Открыто обращение по диалогу #' . $conversation->id)
            ->assertSee('Подозрение на мошенничество')
            ->assertSee('Исходный диалог')
            ->assertSee('Откройте участников или исходный диалог')
            ->assertSee(route('chats.show', $conversation), false)
            ->assertSee(e('<script>alert(1)</script>'), false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $supportConversation))
            ->assertOk()
            ->assertSee('Открыто обращение по диалогу #' . $conversation->id)
            ->assertSee('Обращение в поддержку')
            ->assertSee('Исходный диалог')
            ->assertSee('Покупатель')
            ->assertSee('Продавец')
            ->assertSee(route('admin.chats.show', $conversation), false)
            ->assertSee('Dispute Seller');
    }

    public function test_admin_opening_support_chat_from_marketplace_adds_context_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Context Buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Context Seller']);
        $seller->shop()->create(['name' => 'Context Shop']);
        $product = $this->createProduct($seller, ['title' => 'Context Product']);

        $marketplaceConversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'context_key' => Conversation::productContextKey($product),
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.chats.support.start', $buyer), [
                'source_conversation_id' => $marketplaceConversation->id,
            ])
            ->assertRedirect();

        $supportConversation = Conversation::where('conversation_type', Conversation::TYPE_SUPPORT)
            ->where('buyer_id', $buyer->id)
            ->firstOrFail();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $supportConversation->id,
            'sender_id' => $admin->id,
            'type' => Message::TYPE_SYSTEM,
            'related_conversation_id' => $marketplaceConversation->id,
            'body' => "Поддержка открыла этот чат по обращению из диалога #{$marketplaceConversation->id}.\n"
                . "Товар: Context Product\n"
                . "Покупатель: Context Buyer\n"
                . "Продавец: Context Shop\n"
                . 'Опишите здесь детали обращения, решение или следующий шаг.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $supportConversation))
            ->assertOk()
            ->assertSee('Обращение в поддержку')
            ->assertSee('Диалог #' . $marketplaceConversation->id)
            ->assertSee('Context Product')
            ->assertSee('Исходный диалог')
            ->assertSee(route('admin.chats.show', $marketplaceConversation), false);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $marketplaceConversation))
            ->assertOk()
            ->assertSee('name="source_conversation_id"', false)
            ->assertSee('value="' . $marketplaceConversation->id . '"', false);
    }

    public function test_admin_cannot_attach_unrelated_marketplace_dialogue_to_support_chat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'buyer', 'name' => 'Target Buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer', 'name' => 'Other Buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Private Seller']);
        $seller->shop()->create(['name' => 'Private Shop']);

        $marketplaceConversation = Conversation::create([
            'buyer_id' => $otherBuyer->id,
            'seller_id' => $seller->id,
            'context_key' => Conversation::generalContextKey(),
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.chats.support.start', $target), [
                'source_conversation_id' => $marketplaceConversation->id,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('conversations', [
            'context_key' => 'support:' . $target->id,
        ]);
    }

    public function test_admin_support_chat_has_quick_replies_enter_send_and_unread_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Unread Support Buyer']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:' . $buyer->id,
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Unread support message',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.chats.index'), false)
            ->assertSee('bg-rose-500', false);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('Unread support message')
            ->assertSee('Быстрые ответы')
            ->assertSee('Мы видим обращение и проверим переписку')
            ->assertSee('requestSubmit()', false)
            ->assertSee('bg-rose-500', false);

        $this->assertNotNull($conversation->messages()->first()->fresh()->read_at);
    }

    public function test_admin_support_chat_shows_read_status_for_own_messages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:read-status',
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $admin->id,
            'body' => 'Unread by user support answer',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('Unread by user support answer')
            ->assertSee('title="Отправлено"', false)
            ->assertSee('wv-read-status ', false);

        $conversation->messages()->first()->update(['read_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('wv-read-status is-read', false)
            ->assertSee('title="Прочитано"', false);
    }

    public function test_admin_chat_tabs_show_unread_counts_and_prioritize_unread_dialogs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Admin Unread Buyer']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Admin Unread Seller']);

        $readRecent = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:read',
            'last_message_at' => now(),
        ]);
        $unreadOld = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:old',
            'last_message_at' => now()->subDays(2),
        ]);
        $unreadFresh = Conversation::create([
            'buyer_id' => $seller->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:fresh',
            'last_message_at' => now()->subDay(),
        ]);
        $marketplaceUnread = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'conversation_type' => Conversation::TYPE_MARKETPLACE,
            'last_message_at' => now()->subHours(2),
        ]);

        $readRecent->messages()->create([
            'sender_id' => $admin->id,
            'body' => 'Admin read recent',
            'read_at' => now(),
        ]);
        $unreadOld->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Admin unread old',
        ]);
        $unreadFresh->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Admin unread fresh',
        ]);
        $marketplaceUnread->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Marketplace unread for admin',
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.chats.index', ['mode' => Conversation::TYPE_SUPPORT]))
            ->assertOk()
            ->assertSee('Support-чаты')
            ->assertSee('Marketplace')
            ->assertSee('data-admin-chat-unread="3"', false)
            ->assertSee('data-admin-support-unread="2"', false)
            ->assertSee('data-admin-marketplace-unread="1"', false)
            ->assertSee('Покупатель Бронза')
            ->getContent();

        $this->assertLessThan(
            strpos($content, 'Admin unread old'),
            strpos($content, 'Admin unread fresh')
        );
        $this->assertLessThan(
            strpos($content, 'Admin read recent'),
            strpos($content, 'Admin unread old')
        );
    }

    public function test_admin_reading_marketplace_chat_clears_only_admin_unread_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'conversation_type' => Conversation::TYPE_MARKETPLACE,
            'last_message_at' => now(),
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Unread marketplace message for admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chats.index', ['mode' => Conversation::TYPE_MARKETPLACE]))
            ->assertOk()
            ->assertSee('data-admin-marketplace-unread="1"', false);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk();

        $message->refresh();

        $this->assertNull($message->read_at);
        $this->assertNotNull($message->admin_read_at);

        $this->actingAs($admin)
            ->get(route('admin.chats.index', ['mode' => Conversation::TYPE_MARKETPLACE]))
            ->assertOk()
            ->assertDontSee('data-admin-marketplace-unread=', false)
            ->assertDontSee('data-admin-chat-unread=', false);
    }

    public function test_user_can_hide_own_chat_without_removing_it_for_admin_or_other_participant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'conversation_type' => Conversation::TYPE_MARKETPLACE,
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $seller->id,
            'body' => 'Chat that buyer will hide',
        ]);

        $this->actingAs($buyer)
            ->delete(route('chats.destroy', $conversation))
            ->assertRedirect(route('chats.index'));

        $conversation->refresh();

        $this->assertNotNull($conversation->buyer_deleted_at);
        $this->assertNull($conversation->seller_deleted_at);

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertNotFound();

        $this->actingAs($seller)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Chat that buyer will hide');

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('Chat that buyer will hide');

        $this->actingAs($seller)
            ->post(route('chats.messages.store', $conversation), [
                'body' => 'New message restores hidden chat',
            ])
            ->assertRedirect(route('chats.show', $conversation));

        $this->assertNull($conversation->fresh()->buyer_deleted_at);
    }

    public function test_admin_can_hide_chat_without_removing_it_for_participants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'conversation_type' => Conversation::TYPE_MARKETPLACE,
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Chat hidden by admin only',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.chats.destroy', $conversation))
            ->assertRedirect(route('admin.chats.index', ['mode' => Conversation::TYPE_MARKETPLACE]));

        $this->assertNotNull($conversation->fresh()->admin_deleted_at);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertNotFound();

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Chat hidden by admin only');
    }

    public function test_admin_internal_chat_note_is_hidden_from_participants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Visible marketplace message',
        ]);

        $noteResponse = $this->actingAs($admin)
            ->postJson(route('admin.chats.note', $conversation), [
                'body' => 'Only admins should see this note',
            ])
            ->assertCreated()
            ->assertJsonPath('latest_message_id', fn ($id) => is_int($id) && $id > 0);

        $this->assertStringContainsString('Only admins should see this note', $noteResponse->json('html'));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'type' => 'internal_note',
            'body' => 'Only admins should see this note',
        ]);
        $this->assertSame('Visible marketplace message', $conversation->fresh()->lastMessage->body);

        $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk()
            ->assertSee('Внутренняя заметка')
            ->assertSee('Only admins should see this note');

        $this->actingAs($buyer)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('Visible marketplace message')
            ->assertDontSee('Only admins should see this note');
    }

    public function test_admin_chat_index_opens_mobile_list_before_selected_chat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'last_message_at' => now(),
        ]);

        $indexResponse = $this->actingAs($admin)
            ->get(route('admin.chats.index', ['mode' => Conversation::TYPE_MARKETPLACE]))
            ->assertOk();

        $this->assertStringContainsString('mobileListOpen: true', $indexResponse->getContent());
        $this->assertStringContainsString('Выберите диалог', $indexResponse->getContent());
        $this->assertStringNotContainsString('x-ref="messages"', $indexResponse->getContent());

        $showResponse = $this->actingAs($admin)
            ->get(route('admin.chats.show', $conversation))
            ->assertOk();

        $this->assertStringContainsString('mobileListOpen: false', $showResponse->getContent());
    }

    public function test_non_admin_cannot_open_admin_chats(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->get(route('admin.chats.index'))
            ->assertForbidden();
    }

    public function test_seller_navigation_contains_chats_link(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->view('layouts.seller', ['slot' => ''])
            ->assertSee('Работа')
            ->assertSee('Каталог')
            ->assertSee('Финансы и рост')
            ->assertSee('Управление')
            ->assertSee('Чаты')
            ->assertSee(route('chats.index'), false);
    }

    public function test_seller_chat_pages_use_seller_layout(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $this->actingAs($seller)
            ->get(route('chats.index'))
            ->assertOk()
            ->assertSee('WebVitrina Seller')
            ->assertSee('Назад в кабинет')
            ->assertSee('data-mobile-bottom-seller-nav', false);

        $this->actingAs($seller)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('WebVitrina Seller');
    }

    public function test_seller_public_product_page_uses_seller_mobile_bottom_nav(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Seller product shop']);
        $product = $this->createProduct($seller);

        $this->actingAs($seller)
            ->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertDontSee('data-mobile-bottom-nav', false)
            ->assertSee('data-mobile-bottom-seller-nav', false);
    }

    public function test_seller_cannot_delete_gallery_image_from_another_product(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);

        $ownImage = 'products/gallery/own.jpg';
        $otherImage = 'products/gallery/other.jpg';

        Storage::disk('public')->put($ownImage, 'own image');
        Storage::disk('public')->put($otherImage, 'other image');

        $ownProduct = $this->createProduct($seller, ['gallery' => [$ownImage]]);
        $otherProduct = $this->createProduct($otherSeller, ['gallery' => [$otherImage]]);

        $this->actingAs($seller)
            ->deleteJson(route('seller.products.gallery.delete', $ownProduct), [
                'path' => $otherImage,
            ])
            ->assertForbidden();

        Storage::disk('public')->assertExists($otherImage);
        $this->assertSame([$otherImage], $otherProduct->fresh()->gallery);
    }

    public function test_draft_product_is_not_publicly_viewable(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'draft']);

        $this->get(route('product.show', $product->slug))
            ->assertNotFound();
    }

    public function test_product_page_cache_is_cleared_when_product_becomes_draft(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'active']);

        Cache::put("product_page:{$product->slug}", $product, 600);

        app(ProductCrudRepository::class)->update($product, ['status' => 'draft']);

        $this->assertFalse(Cache::has("product_page:{$product->slug}"));

        $this->get(route('product.show', $product->slug))
            ->assertNotFound();
    }

    public function test_draft_product_cannot_be_added_to_cart_or_quick_checkout(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'draft']);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 1])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->postJson(route('checkout.quick', $product), ['qty' => 1])
            ->assertNotFound();
    }

    public function test_draft_product_cannot_be_favorited_or_shown_on_public_seller_page(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Public seller shop']);
        $draft = $this->createProduct($seller, [
            'title' => 'Hidden draft product',
            'status' => 'draft',
        ]);
        $active = $this->createProduct($seller, [
            'title' => 'Visible active product',
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $draft))
            ->assertNotFound();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $draft->id,
        ]);

        $this->get(route('seller.show', $seller->shop->slug))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee($draft->title);
    }

    public function test_numeric_seller_url_redirects_to_shop_slug(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Slug seller']);

        $this->get(route('seller.show', $seller->id))
            ->assertRedirect(route('seller.show', $shop->slug));
    }

    public function test_public_user_page_shows_safe_profile_without_private_contacts(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Public Safe Seller',
            'email' => 'private-seller@example.test',
            'phone' => '+37377777777',
            'phone_verified_at' => now(),
        ]);
        $shop = $seller->shop()->create([
            'name' => 'Safe Seller Shop',
            'description' => 'Public shop description',
        ]);

        $this->get(route('users.public.show', $seller))
            ->assertOk()
            ->assertSee('Public Safe Seller')
            ->assertSee('Продавец')
            ->assertSee('Safe Seller Shop')
            ->assertSee(route('seller.show', $shop->slug), false)
            ->assertDontSee('private-seller@example.test')
            ->assertDontSee('+37377777777');
    }

    public function test_account_phone_cannot_duplicate_another_users_shop_phone(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create([
            'name' => 'Phone owner shop',
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
        ]);
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'phone' => null,
        ]);

        $this->actingAs($buyer)
            ->patch(route('profile.update'), [
                'profile_section' => 'contacts',
                'email' => $buyer->email,
                'phone' => '+373 77 111 222',
                'phone_dirty' => true,
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertNull($buyer->fresh()->phone);
    }

    public function test_admin_views_public_user_profile_inside_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Preview Seller']);
        $seller->shop()->create(['name' => 'Preview Seller Shop']);

        $this->actingAs($admin)
            ->get(route('users.public.show', $seller))
            ->assertOk()
            ->assertSee('Режим администратора')
            ->assertSee('Публичная карточка: Preview Seller')
            ->assertSee('Admin Panel')
            ->assertSee(route('admin.users.show', $seller), false)
            ->assertSee(route('admin.users.edit', $seller), false)
            ->assertSee(route('admin.chats.support.start', $seller), false)
            ->assertDontSee('-Категории')
            ->assertDontSee('data-mobile-bottom-nav', false)
            ->assertDontSee('data-mobile-bottom-seller-nav', false);
    }

    public function test_user_can_follow_and_unfollow_shop(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Followable shop']);

        $this->actingAs($buyer)
            ->post(route('shops.follow', $shop))
            ->assertRedirect();

        $this->assertDatabaseHas('shop_followers', [
            'shop_id' => $shop->id,
            'user_id' => $buyer->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('seller.show', $shop->slug))
            ->assertOk()
            ->assertSee('Вы подписаны')
            ->assertSee('Подписчики магазина');

        $this->actingAs($buyer)
            ->post(route('shops.follow', $shop))
            ->assertRedirect();

        $this->assertDatabaseMissing('shop_followers', [
            'shop_id' => $shop->id,
            'user_id' => $buyer->id,
        ]);
    }

    public function test_seller_cannot_follow_own_shop(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Own shop']);

        $this->actingAs($seller)
            ->post(route('shops.follow', $shop))
            ->assertStatus(422);

        $this->assertDatabaseMissing('shop_followers', [
            'shop_id' => $shop->id,
            'user_id' => $seller->id,
        ]);
    }

    public function test_seller_can_view_shop_followers_from_panel(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Seller audience shop']);
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'name' => 'Follower Buyer',
        ]);

        $shop->followers()->attach($buyer->id);

        $this->actingAs($seller)
            ->get(route('seller.followers.index'))
            ->assertOk()
            ->assertSee('Подписчики')
            ->assertSee('Follower Buyer')
            ->assertSee(route('users.public.show', $buyer), false)
            ->assertSee('1');
    }

    public function test_seller_menu_shows_followers_link_and_count(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Seller menu audience']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $shop->followers()->attach($buyer->id);

        $this->actingAs($seller)
            ->get(route('seller.cabinet'))
            ->assertOk()
            ->assertSee(route('seller.followers.index'), false)
            ->assertSee('Подписчики')
            ->assertSee(route('support'), false)
            ->assertSee('Поддержка');
    }

    public function test_seller_cabinet_shows_action_center_for_real_tasks(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Action Buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Action Seller Shop']);
        $pendingOrder = $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        $cancelOrder = $this->createOrder($buyer, $seller, Order::STATUS_PROCESSING);
        $cancelOrder->update([
            'cancellation_requested_at' => now(),
            'cancellation_reason' => 'Покупатель ошибся.',
        ]);
        $this->createProduct($seller, [
            'title' => 'Empty action product',
            'status' => 'active',
            'stock' => 0,
        ]);
        $this->createProduct($seller, [
            'title' => 'Low action product',
            'status' => 'active',
            'stock' => 2,
        ]);
        $this->createProduct($seller, [
            'title' => 'Draft action product',
            'status' => 'draft',
        ]);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'seller-action-center',
        ]);
        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Unread seller question',
        ]);

        $this->actingAs($seller)
            ->get(route('seller.cabinet'))
            ->assertOk()
            ->assertSee('Требует внимания')
            ->assertSee('Новые заказы')
            ->assertSee('Запросы отмены')
            ->assertSee('Непрочитанные чаты')
            ->assertSee('Нет в наличии')
            ->assertSee(route('seller.orders.index', ['action' => 'cancel_request']), false)
            ->assertSee($pendingOrder->number)
            ->assertSee($cancelOrder->number);
    }

    public function test_buyer_cabinet_links_to_followed_shops_page(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Favorite subscription shop']);

        $shop->followers()->attach($buyer->id);

        $this->actingAs($buyer)
            ->get(route('cabinet'))
            ->assertOk()
            ->assertSee('Мои подписки')
            ->assertSee(route('subscriptions.index'), false)
            ->assertSee('Favorite subscription shop')
            ->assertDontSee('Стать продавцом');

        $this->actingAs($buyer)
            ->get(route('subscriptions.index'))
            ->assertOk()
            ->assertSee('Favorite subscription shop')
            ->assertSee(route('seller.show', $shop->slug), false)
            ->assertSee('Отписаться')
            ->assertDontSee('Продавать');

        $this->actingAs($buyer)
            ->get(route('subscriptions.index', ['q' => 'Favorite']))
            ->assertOk()
            ->assertSee('Favorite subscription shop')
            ->assertSee('Сбросить');

        $this->actingAs($buyer)
            ->get(route('subscriptions.index', ['q' => 'NoSuchShop']))
            ->assertOk()
            ->assertSee('Ничего не найдено')
            ->assertDontSee('Favorite subscription shop');
    }

    public function test_seller_can_create_active_product(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $payload = $this->validSellerProductPayload([
            'status' => 'active',
        ]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $payload)
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'user_id' => $seller->id,
            'title' => $payload['title'],
            'status' => 'active',
        ]);
    }

    public function test_admin_product_edit_page_renders_modern_operational_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'pro',
        ]);
        $product = $this->createProduct($seller, [
            'title' => 'Admin edit render product',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Основная информация')
            ->assertSee('Категория и локация')
            ->assertSee('Публикация')
            ->assertSee('Pro');
    }

    public function test_admin_product_live_search_escapes_highlighted_product_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('escapeHtml(text)', false)
            ->assertSee('String.fromCharCode(38)', false);
    }

    public function test_admin_products_index_includes_drafts_and_operational_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $active = $this->createProduct($seller, [
            'title' => 'Out of stock published product',
            'stock' => 0,
        ]);
        $draft = $this->createProduct($seller, [
            'title' => 'Private draft product',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertSee($draft->title)
            ->assertSee('Опубликованы без остатка')
            ->assertSee('summaryOpen: false', false)
            ->assertSee('filterOpen: false', false)
            ->assertSee("localStorage.setItem('adminProductsView', mode)", false)
            ->assertSee("setViewMode('list')", false)
            ->assertSee("setViewMode('grid')", false)
            ->assertViewHas('summary', fn (array $summary) => $summary['active'] === 1
                && $summary['draft'] === 1
                && $summary['out_of_stock'] === 1);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee($draft->title)
            ->assertDontSee($active->title);

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['stock' => 'out']))
            ->assertOk()
            ->assertSee('filterOpen: true', false)
            ->assertSee($active->title)
            ->assertDontSee($draft->title);
    }

    public function test_admin_product_delete_confirmation_does_not_interpolate_product_title(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $this->createProduct($seller, ['title' => "Risky '); alert(1); // product"]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee("onsubmit=\"return confirm('Удалить этот товар?')\"", false)
            ->assertDontSee("confirm('Удалить товар Risky", false);
    }

    public function test_admin_cannot_create_product_past_seller_plan_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->createProduct($seller, ['title' => 'Admin limit product ' . $i]);
        }

        $payload = $this->validSellerProductPayload([
            'title' => 'Blocked admin-created product',
            'user_id' => $seller->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('products', [
            'title' => 'Blocked admin-created product',
            'user_id' => $seller->id,
        ]);
    }

    public function test_product_quick_view_keeps_flexible_image_preview(): void
    {
        $css = file_get_contents(public_path('css/product-card.css'));

        $this->assertMatchesRegularExpression('/\\.pm-sheet\\s*\\{[^}]*height:\\s*60vh/s', $css);
        $this->assertMatchesRegularExpression('/\\.pm-main-image-wrap\\s*\\{[^}]*flex:\\s*1;/s', $css);
        $this->assertMatchesRegularExpression('/@media \\(max-width:\\s*640px\\)[\\s\\S]*?\\.pm-main-image-wrap\\s*\\{[^}]*height:\\s*280px/s', $css);
    }

    public function test_seller_product_form_exposes_card_crop_controls(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
        ]);

        $this->actingAs($seller)
            ->get(route('seller.products.create'))
            ->assertOk()
            ->assertSee('data-main-crop="true"', false)
            ->assertSee('id="main-image-crop-canvas"', false)
            ->assertSee('width="500"', false)
            ->assertSee('height="400"', false)
            ->assertSee('aspect-ratio: 4 / 3.2', false)
            ->assertSee('Настроить кадр карточки')
            ->assertSee('Оптимально загружать фото от 1200 x 960 px');
    }

    public function test_seller_product_creation_respects_seller_plan_limit(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->createProduct($seller, ['title' => 'Limit product ' . $i]);
        }

        $payload = $this->validSellerProductPayload([
            'title' => 'Blocked starter product',
        ]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $payload)
            ->assertSessionHasErrors('product_limit');

        $this->assertDatabaseMissing('products', [
            'user_id' => $seller->id,
            'title' => 'Blocked starter product',
        ]);
    }

    public function test_basic_seller_can_create_more_than_starter_limit(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'basic',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->createProduct($seller, ['title' => 'Basic limit product ' . $i]);
        }

        $payload = $this->validSellerProductPayload([
            'title' => 'Allowed basic product',
        ]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $payload)
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'user_id' => $seller->id,
            'title' => 'Allowed basic product',
        ]);
    }

    public function test_seller_product_rejects_svg_images(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $payload = $this->validSellerProductPayload([
            'image' => UploadedFile::fake()->create('product.svg', 1, 'image/svg+xml'),
            'gallery' => [
                UploadedFile::fake()->create('gallery.svg', 1, 'image/svg+xml'),
            ],
        ]);

        $this->actingAs($seller)
            ->postJson(route('seller.products.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image', 'gallery.0']);

        $this->assertDatabaseMissing('products', ['title' => $payload['title']]);
    }

    public function test_cart_rejects_quantity_above_product_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_add_json_reports_out_of_stock_product(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty')
            ->assertJsonFragment(['qty' => ['Товара нет в наличии.']]);
    }

    public function test_cart_item_can_be_removed_as_json_without_page_reload(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $item = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $this->actingAs($buyer)
            ->deleteJson(route('cart.remove', $item))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Товар удалён из корзины',
            ]);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_frontend_toasts_do_not_interpolate_messages_as_html(): void
    {
        $globalToast = file_get_contents(resource_path('js/app.js'));
        $cartToast = file_get_contents(resource_path('views/shop/cart.blade.php'));
        $favoritesToast = file_get_contents(resource_path('views/shop/favorites.blade.php'));
        $profileAvatarToast = file_get_contents(resource_path('js/profile/avatar-cropper.js'));
        $sellerAvatarToast = file_get_contents(resource_path('views/seller/partials/avatar.blade.php'));

        foreach ([$globalToast, $cartToast, $favoritesToast] as $source) {
            $this->assertStringContainsString("textContent = String(text ?? '')", $source);
            $this->assertStringNotContainsString('<span>${text}</span>', $source);
        }

        foreach ([$profileAvatarToast, $sellerAvatarToast] as $source) {
            $this->assertStringContainsString("textContent = String(message ?? '')", $source);
            $this->assertStringNotContainsString('<span>${message}</span>', $source);
        }

        $this->assertStringContainsString('showToast(@js(session(\'success\')));', $favoritesToast);
    }

    public function test_dynamic_seller_product_attributes_escape_admin_configured_text(): void
    {
        $formScript = file_get_contents(resource_path('js/seller-product-form.js'));

        $this->assertStringContainsString('const escapeHtml = value =>', $formScript);
        $this->assertStringContainsString('${escapeHtml(attr.name)}', $formScript);
        $this->assertStringContainsString('${escapeHtml(o)}</option>', $formScript);
        $this->assertStringContainsString('${escapeHtml(selected.name)}</p>', $formScript);
        $this->assertStringContainsString('safeCssColor(color.hex)', $formScript);
        $this->assertStringNotContainsString('>${attr.name}</label>', $formScript);
    }

    public function test_cart_quantities_endpoint_keeps_product_cards_in_sync(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'Quantity synced product',
        ]);

        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 3,
        ]);

        $response = $this->actingAs($buyer)
            ->getJson(route('cart.quantities'))
            ->assertOk()
            ->assertJsonPath("quantities.{$product->id}", 3);

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $this->actingAs($buyer)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Quantity synced product')
            ->assertSee('cart-quantities-refreshed', false);
    }

    public function test_checkout_rejects_cart_quantity_above_current_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 1,
            'status' => 'active',
        ]);

        $cartItem = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.prepare'), [
                'selected_items' => [$cartItem->id],
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('orders', [
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
    }

    public function test_checkout_decrements_stock_when_order_is_created(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 3,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 2,
                    'image' => $product->image,
                ]],
            ])
            ->post(route('checkout.create'), [
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
            ])
            ->assertRedirect();

        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_checkout_displays_and_charges_delivery_for_each_seller_order(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $firstSeller = User::factory()->create(['role' => 'seller', 'name' => 'First seller']);
        $secondSeller = User::factory()->create(['role' => 'seller', 'name' => 'Second seller']);
        $firstSeller->shop()->create(['name' => 'First shop']);
        $secondSeller->shop()->create(['name' => 'Second shop']);
        $firstProduct = $this->createProduct($firstSeller);
        $secondProduct = $this->createProduct($secondSeller);
        $address = UserAddress::create([
            'user_id' => $buyer->id,
            'country' => 'MD',
            'city' => 'Tiraspol',
            'street' => 'Main',
            'house' => '1',
            'is_default' => true,
        ]);
        $cart = collect([$firstProduct, $secondProduct])->map(fn (Product $product) => [
            'cart_id' => null,
            'product_id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'qty' => 1,
            'image' => $product->image,
        ])->all();

        $this->actingAs($buyer)
            ->withSession(['checkout_cart' => $cart])
            ->get(route('checkout.confirm'))
            ->assertOk()
            ->assertSee('First shop')
            ->assertSee('Second shop')
            ->assertSee('Будет создано заказов:')
            ->assertSee('510,00 ₽');

        $this->actingAs($buyer)
            ->post(route('checkout.create'), [
                'checkout_token' => session('checkout_token'),
                'address_id' => $address->id,
                'payment_method' => 'cash',
                'delivery_method' => 'courier',
            ])
            ->assertRedirect(route('orders.index'));

        $totals = Order::where('user_id', $buyer->id)
            ->orderBy('seller_id')
            ->pluck('total_price')
            ->map(fn ($total) => (float) $total)
            ->all();

        $this->assertSame([255.0, 255.0], $totals);
    }

    public function test_checkout_requires_new_confirmation_when_product_price_changes(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $cart = [[
            'cart_id' => null,
            'product_id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'qty' => 1,
            'image' => $product->image,
        ]];

        $this->actingAs($buyer)
            ->withSession(['checkout_cart' => $cart])
            ->get(route('checkout.confirm'))
            ->assertOk();

        $product->update(['price' => 125]);

        $this->actingAs($buyer)
            ->post(route('checkout.create'), [
                'checkout_token' => session('checkout_token'),
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
            ])
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('orders', ['user_id' => $buyer->id]);
        $this->assertSame(125.0, (float) session('checkout_cart.0.price'));
    }

    public function test_checkout_token_cannot_create_the_same_order_twice(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['stock' => 3]);
        $cart = [[
            'cart_id' => null,
            'product_id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'qty' => 1,
            'image' => $product->image,
        ]];

        $this->actingAs($buyer)
            ->withSession(['checkout_cart' => $cart])
            ->get(route('checkout.confirm'))
            ->assertOk();

        $token = session('checkout_token');
        $payload = [
            'checkout_token' => $token,
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
        ];

        $this->actingAs($buyer)
            ->post(route('checkout.create'), $payload)
            ->assertRedirect();

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => $cart,
                'checkout_token' => $token,
            ])
            ->post(route('checkout.create'), $payload)
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHas('error');

        $this->assertSame(1, Order::where('user_id', $buyer->id)->count());
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_buyer_order_page_exposes_only_working_status_actions(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = $seller->shop()->create(['name' => 'Order seller shop']);
        $product = $this->createProduct($seller, ['title' => 'Reviewable order product']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_SHIPPED);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($shop->name)
            ->assertSee('Написать продавцу')
            ->assertSee('Обратиться в поддержку')
            ->assertSee('Подтвердить получение')
            ->assertDontSee('Скачать чек')
            ->assertDontSee('Купить снова');

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Оставить отзыв о покупке')
            ->assertSee('#reviews', false)
            ->assertDontSee('Подтвердить получение');
    }

    public function test_buyer_can_request_cancellation_before_shipping_and_seller_sees_reason(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_PROCESSING);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Запросить отмену');

        $this->actingAs($buyer)
            ->post(route('orders.requestCancellation', $order), [
                'cancellation_reason' => 'Заказ оформлен ошибочно.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'cancellation_reason' => 'Заказ оформлен ошибочно.',
        ]);

        $this->actingAs($seller)
            ->get(route('seller.orders.show', $order))
            ->assertOk()
            ->assertSee('Покупатель запросил отмену заказа')
            ->assertSee('Заказ оформлен ошибочно.');

        $order->update(['status' => Order::STATUS_SHIPPED]);

        $this->actingAs($buyer)
            ->post(route('orders.requestCancellation', $order), [
                'cancellation_reason' => 'Поздний запрос.',
            ])
            ->assertSessionHasErrors('cancellation_reason');
    }

    public function test_phone_verification_routes_require_authentication(): void
    {
        $this->postJson(route('phone.send'))
            ->assertUnauthorized();

        $this->postJson(route('phone.verify'), ['code' => '123456'])
            ->assertUnauthorized();
    }

    public function test_private_local_storage_routes_are_not_registered_by_default(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_web_responses_include_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('x-frame-options', 'SAMEORIGIN')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('referrer-policy', 'strict-origin-when-cross-origin')
            ->assertHeader('permissions-policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader(
                'content-security-policy',
                "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob: https://ui-avatars.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://*.tile.openstreetmap.org; font-src 'self' data: https://fonts.bunny.net https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; connect-src 'self' https://nominatim.openstreetmap.org; frame-src 'self' https://www.youtube.com"
            );
    }

    public function test_large_post_size_shows_friendly_upload_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->withServerVariables(['CONTENT_LENGTH' => 999999999])
            ->post(route('admin.banners.store'), [])
            ->assertRedirect()
            ->assertSessionHasErrors('image_source');
    }

    public function test_currency_proxy_returns_json_not_external_html(): void
    {
        Http::fake([
            'www.agroprombank.com/*' => Http::response('<html><script>alert(1)</script> UAH 0.3650 / 0.4000 MDL 0.9500 / 1.0600</html>'),
        ]);

        $this->getJson('/internal/currency/agroprombank')
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('rates.PRB.PRB', 1)
            ->assertJsonMissing(['script' => 'alert(1)']);
    }

    public function test_currency_switch_rejects_unknown_currency(): void
    {
        $this->postJson(route('currency.set'), ['currency' => '<script>'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_admin_category_rejects_svg_icon_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.categories.store'), [
                'name' => 'Unsafe icon category',
                'slug' => 'unsafe-icon-category',
                'icon' => UploadedFile::fake()->create('icon.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('icon');

        $this->assertDatabaseMissing('categories', [
            'slug' => 'unsafe-icon-category',
        ]);
    }

    public function test_admin_category_images_are_converted_to_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Optimized category',
                'slug' => 'optimized-category',
                'icon' => UploadedFile::fake()->image('icon.png', 512, 512)->size(800),
                'image' => UploadedFile::fake()->image('tile.jpg', 1200, 800)->size(1800),
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'optimized-category')->firstOrFail();

        $this->assertStringStartsWith('categories/icons/', $category->icon);
        $this->assertStringEndsWith('.webp', $category->icon);
        $this->assertStringStartsWith('categories/thumbs/', $category->image);
        $this->assertStringEndsWith('.webp', $category->image);
        Storage::disk('public')->assertExists($category->icon);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_category_update_removes_previous_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $oldIcon = 'categories/icons/old.webp';
        $oldImage = 'categories/thumbs/old.webp';
        Storage::disk('public')->put($oldIcon, 'old icon');
        Storage::disk('public')->put($oldImage, 'old image');

        $category = Category::factory()->create([
            'icon' => $oldIcon,
            'image' => $oldImage,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => UploadedFile::fake()->image('new-icon.png', 512, 512)->size(800),
                'image' => UploadedFile::fake()->image('new-tile.jpg', 1200, 800)->size(1800),
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category->refresh();

        Storage::disk('public')->assertMissing($oldIcon);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($category->icon);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_category_cannot_be_moved_under_itself_or_descendant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->putJson(route('admin.categories.update', $parent), [
                'name' => $parent->name,
                'slug' => $parent->slug,
                'parent_id' => $child->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_admin_category_attribute_changes_clear_filter_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Cache::put("cat.filters.{$category->id}", collect(['stale']), 3600);

        $this->actingAs($admin)
            ->post(route('admin.categories.attributes.store', $category), [
                'name' => 'Filterable attribute',
                'type' => 'select',
                'options' => 'one,two',
            ])
            ->assertRedirect();

        $this->assertFalse(Cache::has("cat.filters.{$category->id}"));
    }

    public function test_admin_category_ajax_search_updates_mobile_and_desktop_fragments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::factory()->create(['name' => 'Visible mobile category']);
        Category::factory()->create(['name' => 'Hidden category']);

        $response = $this->actingAs($admin)
            ->get(route('admin.categories.index', ['ajax' => 1, 'q' => 'Visible mobile']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJsonStructure(['desktop', 'mobile', 'pagination']);

        $this->assertStringContainsString('Visible mobile category', $response->json('desktop'));
        $this->assertStringContainsString('Visible mobile category', $response->json('mobile'));
        $this->assertStringNotContainsString('Hidden category', $response->json('mobile'));
    }

    public function test_admin_attribute_editor_serializes_user_entered_name_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $attribute = \App\Models\Attribute::create([
            'name' => "'; alert('attribute-xss');//",
            'type' => 'text',
        ]);
        $category->attributes()->attach($attribute);

        $this->actingAs($admin)
            ->get(route('admin.categories.attributes', $category))
            ->assertOk()
            ->assertSee('openEditor(', false)
            ->assertDontSee("editName=''; alert('attribute-xss');//", false)
            ->assertDontSee("onsubmit=\"return confirm('Удалить атрибут ';", false);
    }

    public function test_user_cannot_review_product_without_completed_purchase(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Fake review',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_review_product_after_delivery(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->post(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Real review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    public function test_buyer_reviews_page_shows_reviews_written_by_buyer(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'Reviewed product',
        ]);

        Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'Отзыв покупателя виден в кабинете',
            'status' => Review::STATUS_APPROVED,
        ]);

        $this->actingAs($buyer)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee('Reviewed product')
            ->assertSee('Отзыв покупателя виден в кабинете')
            ->assertSee('Одобрен');
    }

    public function test_admin_rejection_reason_is_visible_to_buyer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'Rejected review product',
        ]);
        $review = Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 2,
            'body' => 'Текст требует проверки',
            'status' => Review::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.reviews.reject', $review), [
                'reason' => 'Отзыв содержит неподходящий текст.',
            ])
            ->assertOk()
            ->assertJson(['status' => Review::STATUS_REJECTED]);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'review.rejected',
            'subject_id' => $review->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('reviews.index', ['status' => Review::STATUS_REJECTED]))
            ->assertOk()
            ->assertSee('Причина отклонения')
            ->assertSee('Отзыв содержит неподходящий текст.');
    }

    public function test_admin_cannot_reject_review_without_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $review = Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 1,
            'body' => 'Review requiring a decision',
            'status' => Review::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.reviews.reject', $review), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertSame(Review::STATUS_PENDING, $review->fresh()->status);
    }

    public function test_admin_reviews_can_be_searched_and_filtered_by_rating(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $matchingProduct = $this->createProduct($seller, [
            'title' => 'Searchable camera',
        ]);
        $otherProduct = $this->createProduct($seller, [
            'title' => 'Ordinary kettle',
        ]);

        Review::create([
            'user_id' => $buyer->id,
            'product_id' => $matchingProduct->id,
            'rating' => 1,
            'body' => 'Needs moderator attention',
            'status' => Review::STATUS_PENDING,
        ]);
        Review::create([
            'user_id' => $buyer->id,
            'product_id' => $otherProduct->id,
            'rating' => 5,
            'body' => 'Positive review',
            'status' => Review::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reviews.index', [
                'q' => 'camera',
                'rating' => '1',
            ]))
            ->assertOk()
            ->assertSee('Searchable camera')
            ->assertDontSee('Ordinary kettle');
    }

    public function test_admin_dashboard_analytics_use_real_filtered_data(): void
    {
        Cache::flush();

        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Real dashboard seller']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Buyer with product']);

        for ($i = 0; $i < 12; $i++) {
            $this->createProduct($seller, [
                'title' => 'Dashboard product ' . $i,
            ]);
        }

        $this->createProduct($buyer, [
            'title' => 'Buyer owned dashboard product',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('statDeltas')
            ->assertViewHas('ordersActivity', fn (array $series) => count($series) === 14)
            ->assertViewHas('userGrowth', fn (array $series) => count($series) === 7)
            ->assertViewHas('topSellers', fn ($sellers) => $sellers->contains('id', $seller->id)
                && ! $sellers->contains('id', $buyer->id));

        $this->assertSame(2, $response->viewData('products')->currentPage());
    }

    public function test_admin_dashboard_escapes_category_names_inside_chart_script(): void
    {
        Cache::flush();

        $admin = User::factory()->create(['role' => 'admin']);
        Category::factory()->create([
            'name' => '</script><script>alert("dashboard-xss")</script>',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('</script><script>alert("dashboard-xss")</script>', false)
            ->assertSee('\\\\u003C', false);
    }

    public function test_review_images_are_converted_to_webp_with_thumbnails(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->post(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Review with optimized image',
                'images' => [
                    UploadedFile::fake()->image('review.jpg', 1600, 1200)->size(1200),
                ],
            ])
            ->assertRedirect();

        $review = Review::where('user_id', $buyer->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $image = $review->images()->firstOrFail();

        $this->assertStringContainsString('/medium/', $image->path);
        $this->assertStringEndsWith('.webp', $image->path);

        Storage::disk('public')->assertExists($image->path);
        Storage::disk('public')->assertExists(\App\Services\ImageService::thumbPath($image->path));
    }

    public function test_review_rejects_svg_upload(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Unsafe image',
                'images' => [
                    UploadedFile::fake()->create('review.svg', 1, 'image/svg+xml'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images.0');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_deleting_review_removes_optimized_images(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $review = Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'Review to delete',
            'status' => Review::STATUS_APPROVED,
        ]);

        $path = 'reviews/2026/05/medium/review.webp';
        $thumb = \App\Services\ImageService::thumbPath($path);
        Storage::disk('public')->put($path, 'medium');
        Storage::disk('public')->put($thumb, 'thumb');
        $review->images()->create(['path' => $path]);

        $review->delete();

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($thumb);
    }

    public function test_seller_cannot_cancel_order_after_shipping(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_SHIPPED);

        $this->actingAs($seller)
            ->post(route('seller.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED,
            ])
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_SHIPPED, $order->fresh()->status);
    }

    public function test_last_admin_cannot_demote_or_delete_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => 'buyer',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)
            ->deleteJson(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_users_index_has_operational_filters_and_real_profile_signals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Filtered Seller User',
            'email' => 'filtered-seller@example.com',
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
        ]);
        $seller->shop()->create(['name' => 'Filtered Admin Shop']);
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Hidden Buyer User']);
        $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        $this->createProduct($seller, ['title' => 'Seller admin user product']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'q' => 'Filtered Admin Shop',
                'role' => 'seller',
                'state' => 'phone_verified',
                'sort' => 'products_desc',
            ]))
            ->assertOk()
            ->assertSee('Телефон подтверждён')
            ->assertSee('Продавцы без магазина')
            ->assertSee('Filtered Seller User')
            ->assertSee('Filtered Admin Shop')
            ->assertSee('Серебро')
            ->assertSee(route('admin.chats.support.start', $seller), false)
            ->assertSee('products_desc', false)
            ->assertDontSee('Hidden Buyer User')
            ->assertDontSee('Онлайн')
            ->assertDontSee('Оффлайн');
    }

    public function test_admin_user_show_displays_profile_signals_and_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Show Seller User',
            'email' => 'show-seller@example.com',
            'email_verified_at' => null,
            'phone' => '+37377999000',
            'phone_verified_at' => now(),
        ]);
        $shop = $seller->shop()->create([
            'name' => 'Show Seller Shop',
            'description' => 'Admin profile shop description',
        ]);
        $follower = User::factory()->create(['role' => 'buyer']);
        $shop->followers()->attach($follower->id);
        $product = $this->createProduct($seller, [
            'title' => 'Show user product',
            'stock' => 0,
        ]);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);
        $planRequest = SellerPlanRequest::create([
            'user_id' => $seller->id,
            'current_plan' => 'starter',
            'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
            'message' => 'Нужно больше товаров.',
        ]);
        AdminActivityLog::create([
            'admin_id' => $admin->id,
            'action' => 'seller_plan_request.approved',
            'subject_type' => SellerPlanRequest::class,
            'subject_id' => $planRequest->id,
            'description' => 'Проверено администратором.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $seller))
            ->assertOk()
            ->assertSee('Show Seller User')
            ->assertSee('Show Seller Shop')
            ->assertSee('1 подписчик')
            ->assertSee('Контакты и безопасность')
            ->assertSee('Уровень доверия')
            ->assertSee('Бронзовый уровень')
            ->assertSee('Заказы магазина')
            ->assertSee('Последние заказы магазина')
            ->assertSee('Нужно проверить')
            ->assertSee('Товары без остатка: 1')
            ->assertSee('товаров на витрине')
            ->assertSee('ожидают решения')
            ->assertSee('Запрос:')
            ->assertSee('Starter')
            ->assertSee('Basic')
            ->assertSee('Журнал действий по пользователю')
            ->assertSee('Проверено администратором.')
            ->assertSee('Show user product')
            ->assertSee(route('admin.chats.support.start', $seller), false)
            ->assertSee(route('admin.users.edit', $seller), false)
            ->assertSee(route('admin.chats.index', ['q' => $seller->email]), false)
            ->assertSee(route('admin.products.index', ['seller_id' => $seller->id, 'status' => 'active', 'stock' => 'out']))
            ->assertSee(route('admin.products.edit', $product), false);
    }

    public function test_admin_user_edit_updates_profile_phone_and_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'name' => 'Editable Buyer',
            'email' => 'editable-buyer@example.com',
            'phone' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $buyer))
            ->assertOk()
            ->assertSee('Основные данные')
            ->assertSee('Смена роли влияет на доступы')
            ->assertSee('name="phone"', false)
            ->assertSee('name="password_confirmation"', false);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $buyer), [
                'name' => 'Edited Buyer',
                'email' => 'edited-buyer@example.com',
                'phone' => '+373 77 111 222',
                'role' => 'buyer',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $buyer->refresh();

        $this->assertSame('Edited Buyer', $buyer->name);
        $this->assertSame('edited-buyer@example.com', $buyer->email);
        $this->assertSame('+37377111222', $buyer->phone);
        $this->assertNotNull($buyer->password_set_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $buyer->password));
    }

    public function test_admin_user_delete_confirmation_does_not_interpolate_user_name_into_javascript(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create([
            'role' => 'buyer',
            'name' => "');alert('admin-xss');//",
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $buyer))
            ->assertOk()
            ->assertSee("confirm('Удалить этого пользователя?')", false)
            ->assertDontSee("confirm('Удалить пользователя ", false);
    }

    public function test_admin_profile_displays_account_security_and_recent_activity(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Profile Admin',
            'password_set_at' => now(),
        ]);

        AdminActivityLog::create([
            'admin_id' => $admin->id,
            'action' => 'user.updated',
            'description' => 'Администратор изменил пользователя.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.profile'))
            ->assertOk()
            ->assertSee('Profile Admin')
            ->assertSee('Личные данные')
            ->assertSee('Безопасность')
            ->assertSee('Текущий пароль')
            ->assertSee('Последние действия')
            ->assertSee('Профиль пользователя изменён');
    }

    public function test_admin_must_confirm_current_password_to_change_own_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-original@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('original-password'),
            'password_set_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => $admin->name,
                'email' => 'admin-new@example.com',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame('admin-original@example.com', $admin->fresh()->email);
    }

    public function test_admin_email_change_clears_previous_email_verification(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'verified-admin@example.com',
            'email_verified_at' => now(),
            'password' => \Illuminate\Support\Facades\Hash::make('original-password'),
            'password_set_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => $admin->name,
                'email' => 'unverified-new-admin@example.com',
                'current_password' => 'original-password',
            ])
            ->assertRedirect(route('admin.profile'));

        $admin->refresh();
        $this->assertSame('unverified-new-admin@example.com', $admin->email);
        $this->assertNull($admin->email_verified_at);
    }

    public function test_admin_profile_update_is_recorded_in_activity_log(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-profile@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('original-password'),
            'password_set_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => 'Updated Admin Name',
                'email' => $admin->email,
            ])
            ->assertRedirect(route('admin.profile'));

        $this->assertSame('Updated Admin Name', $admin->fresh()->name);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'profile.updated',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
        ]);
    }

    public function test_admin_can_change_seller_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
            'phone' => '+37377111000',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $seller), [
                'name' => $seller->name,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'role' => 'seller',
                'seller_plan' => 'basic',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('basic', $seller->fresh()->seller_plan);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'user.updated',
            'subject_type' => User::class,
            'subject_id' => $seller->id,
        ]);
    }

    public function test_admin_cannot_assign_seller_plan_below_existing_product_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'pro',
            'phone' => '+37377111001',
        ]);

        for ($i = 0; $i < 26; $i++) {
            $this->createProduct($seller, ['title' => 'Downgrade product ' . $i]);
        }

        $this->actingAs($admin)
            ->put(route('admin.users.update', $seller), [
                'name' => $seller->name,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'role' => 'seller',
                'seller_plan' => 'basic',
            ])
            ->assertSessionHasErrors('seller_plan');

        $this->assertSame('pro', $seller->fresh()->seller_plan);
    }

    public function test_seller_can_request_plan_upgrade_and_admin_can_approve_it(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($seller)
            ->get(route('seller.plans.index'))
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Enterprise');

        $this->actingAs($seller)
            ->post(route('seller.plans.request'), [
                'requested_plan' => 'pro',
                'message' => 'Хочу больше товаров и аналитику.',
            ])
            ->assertRedirect();

        $planRequest = SellerPlanRequest::firstOrFail();

        $this->assertSame(SellerPlanRequest::STATUS_PENDING, $planRequest->status);
        $this->assertSame('starter', $planRequest->current_plan);
        $this->assertSame('pro', $planRequest->requested_plan);

        $this->actingAs($admin)
            ->post(route('admin.seller-plan-requests.approve', $planRequest), [
                'admin_note' => 'Оплата подтверждена вручную.',
            ])
            ->assertRedirect();

        $this->assertSame('pro', $seller->fresh()->seller_plan);
        $this->assertSame(SellerPlanRequest::STATUS_APPROVED, $planRequest->fresh()->status);
        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_id' => $admin->id,
            'action' => 'seller_plan_request.approved',
            'subject_type' => SellerPlanRequest::class,
            'subject_id' => $planRequest->id,
        ]);
    }

    public function test_seller_cannot_create_duplicate_pending_plan_request(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
        ]);

        SellerPlanRequest::create([
            'user_id' => $seller->id,
            'current_plan' => 'starter',
            'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);

        $this->actingAs($seller)
            ->post(route('seller.plans.request'), [
                'requested_plan' => 'pro',
            ])
            ->assertSessionHasErrors('requested_plan');

        $this->assertSame(1, SellerPlanRequest::count());
    }

    public function test_seller_can_request_plan_downgrade_and_admin_menu_shows_pending_badge(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'pro',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($seller)
            ->post(route('seller.plans.request'), [
                'requested_plan' => 'basic',
                'message' => 'Хочу перейти на тариф ниже.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('seller_plan_requests', [
            'user_id' => $seller->id,
            'current_plan' => 'pro',
            'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Тарифы')
            ->assertSee('1', false);
    }

    public function test_admin_cannot_approve_plan_downgrade_below_catalog_size(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'pro',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 26; $i++) {
            $this->createProduct($seller, ['title' => 'Plan request product ' . $i]);
        }

        $request = SellerPlanRequest::create([
            'user_id' => $seller->id,
            'current_plan' => 'pro',
            'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.seller-plan-requests.approve', $request))
            ->assertSessionHasErrors('requested_plan');

        $this->assertSame('pro', $seller->fresh()->seller_plan);
        $this->assertSame(SellerPlanRequest::STATUS_PENDING, $request->fresh()->status);
    }

    public function test_admin_plan_approval_rejects_oversized_note(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'seller_plan' => 'starter',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $request = SellerPlanRequest::create([
            'user_id' => $seller->id,
            'current_plan' => 'starter',
            'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.seller-plan-requests.approve', $request), [
                'admin_note' => str_repeat('a', 701),
            ])
            ->assertSessionHasErrors('admin_note');

        $this->assertSame('starter', $seller->fresh()->seller_plan);
        $this->assertSame(SellerPlanRequest::STATUS_PENDING, $request->fresh()->status);
    }

    public function test_admin_banner_rejects_javascript_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.banners.store'), [
                'title' => 'Unsafe banner',
                'link' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('link');

        $this->assertDatabaseMissing('banners', [
            'title' => 'Unsafe banner',
        ]);
    }

    public function test_admin_banner_create_page_can_be_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.banners.create'))
            ->assertOk()
            ->assertSee('Новый баннер')
            ->assertSee('Создать баннер')
            ->assertSee('2400 x 720')
            ->assertSee('aspect-[30/9]', false);
    }

    public function test_admin_banner_edit_page_previews_legacy_image_and_exposes_cropper_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $banner = Banner::create([
            'title' => 'Legacy image banner',
            'image' => 'banners/legacy/old.webp',
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.banners.edit', $banner))
            ->assertOk()
            ->assertSee('storage/banners/legacy/old.webp', false)
            ->assertSee('id="banner-open-crop"', false)
            ->assertSee('id="banner-recrop-existing"', false);
    }

    public function test_admin_banners_index_has_operational_filters_and_device_signals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $activeBanner = Banner::create([
            'title' => 'Homepage spring campaign',
            'image' => 'banners/legacy/spring.webp',
            'image_desktop' => 'banners/desktop/spring.webp',
            'image_tablet' => 'banners/tablet/spring.webp',
            'image_mobile' => 'banners/mobile/spring.webp',
            'link' => '/catalog?spring=1',
            'sort_order' => 2,
            'active' => true,
        ]);

        Banner::create([
            'title' => 'Hidden winter campaign',
            'image' => 'banners/legacy/winter.webp',
            'image_desktop' => 'banners/desktop/winter.webp',
            'sort_order' => 1,
            'active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.banners.index', [
                'q' => 'spring',
                'status' => 'active',
                'sort' => 'order_desc',
            ]))
            ->assertOk()
            ->assertSee('Mobile-ready')
            ->assertSee('Homepage spring campaign')
            ->assertSee('Desktop')
            ->assertSee('Tablet')
            ->assertSee('Mobile')
            ->assertSee(route('admin.banners.edit', $activeBanner), false)
            ->assertSee('order_desc', false)
            ->assertDontSee('Hidden winter campaign');
    }

    public function test_admin_banner_rejects_svg_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.banners.store'), [
                'title' => 'Unsafe banner image',
                'image_desktop' => UploadedFile::fake()->create('banner.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image_desktop');

        $this->assertDatabaseMissing('banners', [
            'title' => 'Unsafe banner image',
        ]);
    }

    public function test_admin_banner_images_are_converted_to_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'Optimized banner',
                'active' => '1',
                'image_desktop' => UploadedFile::fake()->image('desktop.jpg', 2400, 800)->size(2500),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Optimized banner')->firstOrFail();

        $this->assertStringStartsWith('banners/desktop/', $banner->image_desktop);
        $this->assertStringEndsWith('.webp', $banner->image_desktop);
        Storage::disk('public')->assertExists($banner->image_desktop);

        $image = (new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver()))
            ->read(Storage::disk('public')->path($banner->image_desktop));

        $this->assertSame(2400, $image->width());
        $this->assertSame(720, $image->height());
    }

    public function test_admin_banner_single_source_creates_all_device_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'Single source banner',
                'active' => '1',
                'crop_x' => '10',
                'crop_y' => '10',
                'crop_w' => '80',
                'crop_h' => '70',
                'image_source' => UploadedFile::fake()->image('hero.jpg', 2400, 1200)->size(2500),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Single source banner')->firstOrFail();

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $path = $banner->{"image_{$device}"};
            $this->assertStringStartsWith("banners/{$device}/", $path);
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_admin_banner_mobile_upload_overrides_single_source_mobile_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'Banner with mobile override',
                'active' => '1',
                'image_source' => UploadedFile::fake()->image('hero.jpg', 2400, 1200)->size(2500),
                'mobile_crop_x' => '20',
                'mobile_crop_y' => '10',
                'mobile_crop_w' => '60',
                'mobile_crop_h' => '80',
                'image_mobile' => UploadedFile::fake()->image('mobile.jpg', 2400, 1600)->size(1200),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Banner with mobile override')->firstOrFail();

        $this->assertStringStartsWith('banners/desktop/', $banner->image_desktop);
        $this->assertStringStartsWith('banners/tablet/', $banner->image_tablet);
        $this->assertStringStartsWith('banners/mobile/', $banner->image_mobile);
        Storage::disk('public')->assertExists($banner->image_mobile);

        $image = (new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver()))
            ->read(Storage::disk('public')->path($banner->image_mobile));

        $this->assertSame(960, $image->width());
        $this->assertSame(480, $image->height());
    }

    public function test_admin_banner_update_removes_previous_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $oldPath = 'banners/desktop/old.webp';
        Storage::disk('public')->put($oldPath, 'old banner');

        $banner = Banner::create([
            'title' => 'Banner with old image',
            'image_desktop' => $oldPath,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.banners.update', $banner), [
                'title' => 'Banner with new image',
                'active' => '1',
                'image_desktop' => UploadedFile::fake()->image('new.jpg', 2400, 800)->size(2500),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertStringStartsWith('banners/desktop/', $banner->image_desktop);
        $this->assertStringEndsWith('.webp', $banner->image_desktop);
        Storage::disk('public')->assertExists($banner->image_desktop);
    }

    public function test_admin_banner_can_recrop_existing_legacy_image_without_reupload(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $legacyPath = UploadedFile::fake()
            ->image('legacy.jpg', 2400, 1200)
            ->storeAs('banners/legacy', 'old.jpg', 'public');

        $banner = Banner::create([
            'title' => 'Legacy recrop banner',
            'image' => $legacyPath,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.banners.update', $banner), [
                'title' => 'Legacy recrop banner',
                'active' => '1',
                'recrop_existing' => '1',
                'crop_x' => '10',
                'crop_y' => '10',
                'crop_w' => '80',
                'crop_h' => '70',
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        $this->assertNull($banner->image);

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $path = $banner->{"image_{$device}"};
            $this->assertStringStartsWith("banners/{$device}/", $path);
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
        }

        Storage::disk('public')->assertMissing($legacyPath);
    }

    public function test_seller_shop_rejects_javascript_social_link(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Seller shop',
                'facebook' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('facebook');
    }

    public function test_seller_shop_rejects_svg_banner_upload(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Seller shop',
                'banner' => UploadedFile::fake()->create('banner.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('banner');
    }

    public function test_admin_cannot_update_attribute_through_unrelated_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::factory()->create();
        $otherCategory = \App\Models\Category::factory()->create();
        $attribute = \App\Models\Attribute::create([
            'name' => 'Size',
            'type' => 'select',
            'options' => ['S', 'M'],
        ]);

        $category->attributes()->attach($attribute->id);

        $this->actingAs($admin)
            ->putJson(route('admin.categories.attributes.update', [$otherCategory, $attribute]), [
                'name' => 'Hacked size',
                'type' => 'select',
                'options' => 'L,XL',
            ])
            ->assertNotFound();

        $this->assertSame('Size', $attribute->fresh()->name);
    }

    public function test_admin_detaching_shared_attribute_keeps_color_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::factory()->create();
        $otherCategory = \App\Models\Category::factory()->create();
        $color = \App\Models\Color::create(['name' => 'Red', 'hex' => '#ff0000']);
        $attribute = \App\Models\Attribute::create([
            'name' => 'Color',
            'type' => 'color',
            'options' => null,
        ]);

        $category->attributes()->attach($attribute->id);
        $otherCategory->attributes()->attach($attribute->id);
        $attribute->colors()->attach($color->id);

        $this->actingAs($admin)
            ->delete(route('admin.categories.attributes.destroy', [$category, $attribute]))
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_category', [
            'category_id' => $otherCategory->id,
            'attribute_id' => $attribute->id,
        ]);
        $this->assertDatabaseHas('attribute_color', [
            'attribute_id' => $attribute->id,
            'color_id' => $color->id,
        ]);
    }

    public function test_currency_switcher_stores_supported_currency_in_session(): void
    {
        $this->postJson(route('currency.set'), ['currency' => 'RUB'])
            ->assertOk()
            ->assertJson(['currency' => 'PRB'])
            ->assertSessionHas('currency', 'PRB');

        $this->post(route('currency.set'), ['currency' => 'UAH'])
            ->assertRedirect()
            ->assertSessionHas('currency', 'UAH');

        $this->postJson(route('currency.set'), ['currency' => 'EUR'])
            ->assertUnprocessable();
    }

    public function test_product_price_uses_selected_session_currency(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'price' => 200,
            'currency_base' => 'RUB',
            'price_prb' => 200,
            'price_mdl' => 220,
            'price_uah' => 580,
        ]);

        session(['currency' => 'UAH']);

        $price = $product->price_for_current_currency;

        $this->assertSame(580.0, $price['amount']);
        $this->assertSame('UAH', $price['code']);
        $this->assertSame('₴', $price['symbol']);
    }

    public function test_seller_products_index_normalizes_filters_and_uses_all_products_for_summary(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->createProduct($seller, [
            'title' => 'Active seller product',
            'price' => 100,
            'status' => 'active',
        ]);
        $this->createProduct($seller, [
            'title' => 'Draft seller product',
            'price' => 300,
            'status' => 'draft',
            'stock' => 0,
        ]);

        $response = $this->actingAs($seller)
            ->get(route('seller.products.index', [
                'status' => 'published',
                'sort' => 'unexpected',
            ]))
            ->assertOk()
            ->assertViewHas('status', null)
            ->assertViewHas('sort', 'new');

        $this->assertSame(2, (int) $response->viewData('productTotals')->total);
        $this->assertSame(200.0, (float) $response->viewData('productTotals')->avg_price);
        $this->assertSame(1, (int) $response->viewData('productTotals')->out_of_stock);

        $stockResponse = $this->actingAs($seller)
            ->get(route('seller.products.index', ['stock' => 'out']))
            ->assertOk()
            ->assertViewHas('stock', 'out');

        $this->assertSame(1, $stockResponse->viewData('products')->total());
    }

    public function test_seller_orders_index_searches_and_ignores_unknown_status(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Order Search Buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);

        $matchingOrder = $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        $this->createOrder($buyer, $otherSeller, Order::STATUS_PENDING);

        $response = $this->actingAs($seller)
            ->get(route('seller.orders.index', [
                'status' => 'new',
                'q' => 'Order Search',
            ]))
            ->assertOk()
            ->assertViewHas('status', null)
            ->assertSee($matchingOrder->number);

        $this->assertSame(1, $response->viewData('orders')->total());
        $this->assertSame(1, (int) ($response->viewData('statusCounts')[Order::STATUS_PENDING] ?? 0));

        $matchingOrder->update([
            'cancellation_requested_at' => now(),
            'cancellation_reason' => 'Cancel requested',
        ]);

        $actionResponse = $this->actingAs($seller)
            ->get(route('seller.orders.index', ['action' => 'cancel_request']))
            ->assertOk()
            ->assertViewHas('action', 'cancel_request')
            ->assertSee('Запрос отмены');

        $this->assertSame(1, $actionResponse->viewData('orders')->total());
    }

    public function test_seller_order_show_has_work_panel_and_chat_context(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Order Chat Buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['title' => 'Seller order context product']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.orders.show', $order))
            ->assertOk()
            ->assertSee('Рабочая панель продавца')
            ->assertSee('Написать покупателю')
            ->assertSee('Товары в заказе')
            ->assertSee('Seller order context product')
            ->assertSee('Открыть товар')
            ->assertSee($product->image_thumb_url, false)
            ->assertSee(route('seller.orders.chat.buyer', $order), false)
            ->assertSee('Покупатель ждёт');

        $this->actingAs($otherSeller)
            ->post(route('seller.orders.chat.buyer', $order))
            ->assertForbidden();

        $this->actingAs($seller)
            ->post(route('seller.orders.chat.buyer', $order))
            ->assertRedirect();

        $conversation = Conversation::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $this->assertSame($buyer->id, $conversation->buyer_id);
        $this->assertSame($seller->id, $conversation->seller_id);
        $this->assertSame(Conversation::orderProductContextKey($order, $product), $conversation->context_key);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $seller->id,
            'type' => Message::TYPE_SYSTEM,
            'order_id' => $order->id,
            'body' => "Диалог по заказу {$order->number}.\nТовар: Seller order context product",
        ]);
    }

    public function test_order_show_pages_include_shared_timeline_for_all_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['title' => 'Timeline product']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_SHIPPED);
        $order->update([
            'accepted_at' => now()->subDays(2),
            'shipped_at' => now()->subDay(),
            'cancellation_requested_at' => now()->subHours(12),
            'cancellation_reason' => 'Timeline cancellation reason',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Ход заказа')
            ->assertSee('Покупатель запросил отмену')
            ->assertSee('Timeline cancellation reason');

        $this->actingAs($seller)
            ->get(route('seller.orders.show', $order))
            ->assertOk()
            ->assertSee('Ход заказа')
            ->assertSee('Передан в доставку');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Ход заказа')
            ->assertSee('Передан в доставку');
    }

    public function test_buyer_help_questions_and_seller_finance_are_honest_working_pages(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Finance seller shop']);
        $this->createOrder($buyer, $seller, Order::STATUS_COMPLETED);
        $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        $this->createOrder($buyer, $seller, Order::STATUS_CANCELED);

        $this->actingAs($buyer)
            ->get(route('help'))
            ->assertOk()
            ->assertSee('Как оформить заказ?')
            ->assertDontSee('href="#"', false);

        $this->actingAs($buyer)
            ->get(route('questions.index'))
            ->assertOk()
            ->assertSee('Вопросы по товарам пока живут в чатах')
            ->assertDontSee('находится в разработке');

        $this->actingAs($seller)
            ->get(route('seller.finance.index'))
            ->assertOk()
            ->assertSee('Деньги по заказам')
            ->assertSee('Завершено / доставлено')
            ->assertDontSee('Раздел «Финансы» в разработке');
    }

    public function test_cart_keeps_out_of_stock_items_in_unavailable_block(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $available = $this->createProduct($seller, ['title' => 'Available cart product', 'stock' => 3]);
        $unavailable = $this->createProduct($seller, ['title' => 'Out cart product', 'stock' => 0]);
        CartItem::create(['user_id' => $buyer->id, 'product_id' => $available->id, 'qty' => 1]);
        CartItem::create(['user_id' => $buyer->id, 'product_id' => $unavailable->id, 'qty' => 1]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Недоступно для оформления')
            ->assertSee('Сейчас нет в наличии')
            ->assertSee('Out cart product')
            ->assertSee('Available cart product');
    }

    public function test_buyer_order_show_suggests_continuing_purchase(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Continue shop']);
        $category = Category::factory()->create();
        $purchased = $this->createProduct($seller, ['title' => 'Purchased continue product', 'category_id' => $category->id]);
        $related = $this->createProduct($seller, ['title' => 'Related continue product', 'category_id' => $category->id]);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_COMPLETED);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $purchased->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Продолжить покупки')
            ->assertSee('Related continue product');
    }

    public function test_seller_cabinet_checklist_and_product_quality_hints_are_visible(): void
    {
        $seller = User::factory()->create(['role' => 'seller', 'email_verified_at' => null]);
        $seller->shop()->create(['name' => 'Checklist shop', 'phone' => null]);
        $this->createProduct($seller, [
            'title' => 'Quality hint product',
            'image' => 'default/no-image.png',
            'description' => 'Short',
            'stock' => 0,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.cabinet'))
            ->assertOk()
            ->assertSee('Чеклист запуска магазина')
            ->assertSee('Заполнить телефон магазина')
            ->assertDontSee('Добавить баннер');

        $this->actingAs($seller)
            ->get(route('seller.products.index'))
            ->assertOk()
            ->assertSee('Нет фото')
            ->assertSee('Короткое описание')
            ->assertSee('Нет характеристик')
            ->assertSee('Нет остатков');
    }

    public function test_admin_chats_show_priority_badges_and_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $conversation = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'context_key' => 'admin-priority-test',
            'conversation_type' => Conversation::TYPE_SUPPORT,
        ]);
        $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => 'Priority question',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chats.index', ['type' => 'priority']))
            ->assertOk()
            ->assertSee('Приоритетные')
            ->assertSee('Высокий')
            ->assertSee('Priority question');
    }

    public function test_buyer_orders_index_filters_tabs_and_uses_mobile_friendly_layout(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $activeOrder = $this->createOrder($buyer, $seller, Order::STATUS_PENDING);
        $completedOrder = $this->createOrder($buyer, $seller, Order::STATUS_COMPLETED);
        $canceledOrder = $this->createOrder($buyer, $seller, Order::STATUS_CANCELED);

        $this->actingAs($buyer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewHas('tab', 'active')
            ->assertSee($activeOrder->number)
            ->assertDontSee($completedOrder->number)
            ->assertDontSee($canceledOrder->number)
            ->assertDontSee('Статус заказа');

        $this->actingAs($buyer)
            ->get(route('orders.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertViewHas('tab', 'completed')
            ->assertSee($completedOrder->number)
            ->assertDontSee($activeOrder->number);
    }

    public function test_buyer_orders_search_and_action_tab_use_real_order_tasks(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Searchable buyer shop']);
        $shippedProduct = $this->createProduct($seller, ['title' => 'Needs receipt product']);
        $completedProduct = $this->createProduct($seller, ['title' => 'Needs review product']);
        $hiddenProduct = $this->createProduct($seller, ['title' => 'Already reviewed product']);
        $shippedOrder = $this->createOrder($buyer, $seller, Order::STATUS_SHIPPED);
        $completedOrder = $this->createOrder($buyer, $seller, Order::STATUS_COMPLETED);
        $reviewedOrder = $this->createOrder($buyer, $seller, Order::STATUS_COMPLETED);

        foreach ([[$shippedOrder, $shippedProduct], [$completedOrder, $completedProduct], [$reviewedOrder, $hiddenProduct]] as [$order, $product]) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100,
                'total' => 100,
            ]);
        }

        Review::create([
            'user_id' => $buyer->id,
            'product_id' => $hiddenProduct->id,
            'rating' => 5,
            'body' => 'Already done',
            'status' => Review::STATUS_APPROVED,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.index', ['tab' => 'action']))
            ->assertOk()
            ->assertViewHas('tab', 'action')
            ->assertViewHas('actionCount', 2)
            ->assertSee('Мои действия')
            ->assertSee($shippedOrder->number)
            ->assertSee($completedOrder->number)
            ->assertDontSee($reviewedOrder->number)
            ->assertSee('Подтвердите получение товара')
            ->assertSee('Можно оставить отзыв о покупке');

        $this->actingAs($buyer)
            ->get(route('orders.index', ['tab' => 'completed', 'q' => 'Needs review']))
            ->assertOk()
            ->assertSee($completedOrder->number)
            ->assertDontSee($reviewedOrder->number);
    }

    public function test_buyer_cabinet_shows_action_counts_from_orders_and_messages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $shippedOrder = $this->createOrder($buyer, $seller, Order::STATUS_SHIPPED);
        $receivedOrder = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        foreach ([$shippedOrder, $receivedOrder] as $order) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100,
                'total' => 100,
            ]);
        }

        $support = Conversation::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $admin->id,
            'conversation_type' => Conversation::TYPE_SUPPORT,
            'context_key' => 'support:' . $buyer->id,
        ]);
        $support->messages()->create([
            'sender_id' => $admin->id,
            'body' => 'Support reply awaiting buyer',
        ]);

        $this->actingAs($buyer)
            ->get(route('cabinet'))
            ->assertOk()
            ->assertViewHas('unreadMessagesCount', 1)
            ->assertViewHas('confirmationOrdersCount', 1)
            ->assertViewHas('reviewableOrdersCount', 1)
            ->assertViewHas('supportUnreadCount', 1)
            ->assertSee('Требует внимания')
            ->assertSee('Подтвердить получение')
            ->assertSee('Оставить отзыв');
    }

    public function test_cart_index_preserves_and_marks_unavailable_products(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $activeProduct = $this->createProduct($seller, ['title' => 'Visible cart product']);
        $draftProduct = $this->createProduct($seller, [
            'title' => 'Hidden cart product',
            'status' => 'draft',
        ]);

        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $activeProduct->id,
            'qty' => 1,
        ]);
        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $draftProduct->id,
            'qty' => 1,
        ]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Visible cart product')
            ->assertSee('Hidden cart product')
            ->assertSee('Недоступно для оформления')
            ->assertViewHas('items', fn ($items) => $items->contains('product_id', $activeProduct->id)
                && ! $items->contains('product_id', $draftProduct->id))
            ->assertViewHas('unavailableItems', fn ($items) => $items->contains('product_id', $draftProduct->id));

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $activeProduct->id,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $draftProduct->id,
        ]);
    }

    public function test_favorites_index_preserves_and_marks_unavailable_products(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $activeProduct = $this->createProduct($seller, ['title' => 'Visible favorite product']);
        $draftProduct = $this->createProduct($seller, [
            'title' => 'Hidden favorite product',
            'status' => 'draft',
        ]);

        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $activeProduct->id,
        ]);
        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $draftProduct->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Visible favorite product')
            ->assertSee('Hidden favorite product')
            ->assertSee('Больше недоступны')
            ->assertViewHas('items', fn ($items) => $items->contains('product_id', $activeProduct->id)
                && ! $items->contains('product_id', $draftProduct->id))
            ->assertViewHas('unavailableItems', fn ($items) => $items->contains('product_id', $draftProduct->id));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $activeProduct->id,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $draftProduct->id,
        ]);

        $unavailableFavorite = Favorite::where('user_id', $buyer->id)
            ->where('product_id', $draftProduct->id)
            ->firstOrFail();

        $this->actingAs($buyer)
            ->delete(route('favorites.remove', $unavailableFavorite))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $draftProduct->id,
        ]);
    }

    public function test_selected_favorites_can_be_added_to_cart_without_removing_favorites(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $firstProduct = $this->createProduct($seller, ['title' => 'First selected favorite']);
        $secondProduct = $this->createProduct($seller, ['title' => 'Second selected favorite']);
        $unselectedProduct = $this->createProduct($seller, ['title' => 'Still favorite product']);

        $firstFavorite = Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $firstProduct->id,
        ]);
        $secondFavorite = Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $secondProduct->id,
        ]);
        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $unselectedProduct->id,
        ]);

        $this->actingAs($buyer)
            ->post(route('cart.addFavorites'), [
                'favorite_ids' => [$firstFavorite->id, $secondFavorite->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $firstProduct->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $secondProduct->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $firstProduct->id,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $secondProduct->id,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $unselectedProduct->id,
        ]);
    }

    public function test_single_favorite_can_be_added_to_cart_without_removing_favorite(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['title' => 'Move single favorite']);

        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), [
                'qty' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('removed_from_favorites', false);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_buyer_utility_pages_use_mobile_safe_layouts(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'title' => 'КроссовкиTommyHilfigerEM0EM01527BDSбелыессинейподошвойдлинноеназваниебезпробелов',
        ]);

        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 1,
        ]);
        Favorite::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
        UserAddress::create([
            'user_id' => $buyer->id,
            'country' => 'Moldova',
            'city' => 'Chisinau',
            'street' => 'ОченьДлиннаяУлицаБезПробеловДляПроверкиМобильнойВерстки',
            'house' => '123A',
            'comment' => 'ОченьДлинныйКомментарийБезПробеловКоторыйНеДолженВыталкиватьКарточкуЗаЭкран',
            'is_default' => true,
        ]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-mobile-safe', false)
            ->assertSee('КроссовкиTommyHilf...', false);

        $this->actingAs($buyer)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('favorites-mobile-safe', false)
            ->assertSee('КроссовкиTommyHilf...', false);

        $this->actingAs($buyer)
            ->get(route('addresses.index'))
            ->assertOk()
            ->assertSee('addresses-mobile-safe', false)
            ->assertSee('ОченьДлиннаяУлицаБезПробеловДляПроверкиМобильнойВерстки');

        $this->actingAs($buyer)
            ->get(route('notifications.settings'))
            ->assertOk()
            ->assertSee('notifications-mobile-safe', false)
            ->assertSee('Email уведомления');
    }

    public function test_category_page_renders_associative_breadcrumbs(): void
    {
        $root = Category::factory()->create([
            'name' => 'Электроника',
            'slug' => 'electronics',
        ]);
        $category = Category::factory()->create([
            'parent_id' => $root->id,
            'name' => 'Смартфоны',
            'slug' => 'smartfony',
        ]);

        $this->createProduct(User::factory()->create(['role' => 'seller']), [
            'category_id' => $category->id,
            'title' => 'Breadcrumb category product',
        ]);

        $this->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('Категории')
            ->assertSee('Электроника')
            ->assertSee('Смартфоны');
    }

    private function createProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'title' => 'Security test product ' . uniqid(),
            'sku' => 'TEST-' . uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Test product',
            'status' => 'active',
        ], $overrides));
    }

    private function createOrder(User $buyer, User $seller, string $status = Order::STATUS_PENDING): Order
    {
        return Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-SEC-' . uniqid(),
            'status' => $status,
            'total_price' => 100,
            'currency' => 'RUB',
        ]);
    }

    private function validSellerProductPayload(array $overrides = []): array
    {
        $category = \App\Models\Category::factory()->create();
        $country = \App\Models\Country::create(['name' => 'Security country ' . uniqid()]);
        $city = \App\Models\City::create([
            'country_id' => $country->id,
            'name' => 'Security city ' . uniqid(),
        ]);

        return array_merge([
            'title' => 'Seller status product ' . uniqid(),
            'price' => 100,
            'stock' => 5,
            'description' => 'Valid product',
            'category_id' => $category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'status' => 'draft',
        ], $overrides);
    }
}
