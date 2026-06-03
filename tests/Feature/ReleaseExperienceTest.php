<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDispute;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\MarketplaceEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReleaseExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_language_currency_and_notification_preferences(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->patch(route('settings.currency.update'), ['currency' => 'MDL'])
            ->assertRedirect();

        $this->actingAs($buyer)
            ->patch(route('settings.language.update'), ['locale' => 'ro'])
            ->assertRedirect();

        $this->actingAs($buyer)
            ->patch(route('notifications.settings.update'), [
                'site_orders' => '1',
                'site_messages' => '1',
                'email_orders' => '1',
                'email_security' => '0',
            ])
            ->assertRedirect();

        $buyer->refresh();

        $this->assertSame('MDL', $buyer->preferred_currency);
        $this->assertSame('ro', $buyer->locale);
        $this->assertTrue($buyer->notification_preferences['site_orders']);
        $this->assertTrue($buyer->notification_preferences['email_orders']);
        $this->assertFalse($buyer->notification_preferences['email_security']);
    }

    public function test_user_notification_center_marks_notifications_as_read(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $notification = UserNotification::create([
            'user_id' => $buyer->id,
            'type' => 'test',
            'title' => 'Проверка',
            'body' => 'Текст',
        ]);

        $this->actingAs($buyer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Проверка');

        $this->actingAs($buyer)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_buyer_can_open_order_dispute_and_notify_seller(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-DISPUTE',
            'status' => Order::STATUS_SHIPPED,
            'total_price' => 100,
            'currency' => 'PRB',
        ]);

        $this->actingAs($buyer)
            ->post(route('orders.disputes.store', $order), [
                'reason' => 'Товар не получен',
                'details' => 'Покупатель ждёт доставку.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_disputes', [
            'order_id' => $order->id,
            'reason' => 'Товар не получен',
            'status' => OrderDispute::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $seller->id,
            'type' => 'order_dispute_opened',
        ]);
    }

    public function test_product_report_is_stored_for_admin_review(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        User::factory()->create(['role' => 'admin']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Reportable product',
            'slug' => 'reportable-product',
            'price' => 100,
            'stock' => 3,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->post(route('products.report', $product), [
                'reason' => 'Неверное описание',
                'details' => 'Описание выглядит подозрительно.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_reports', [
            'product_id' => $product->id,
            'user_id' => $buyer->id,
            'reason' => 'Неверное описание',
        ]);

        $this->assertSame(1, ProductReport::count());
        $this->assertDatabaseHas('user_notifications', ['type' => 'product_report']);
    }

    public function test_admin_can_review_product_reports_and_hide_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Suspicious product',
            'slug' => 'suspicious-product',
            'price' => 100,
            'stock' => 3,
            'status' => 'active',
        ]);
        $report = ProductReport::create([
            'product_id' => $product->id,
            'user_id' => $buyer->id,
            'reason' => 'Неверное описание',
            'details' => 'Покупатель просит проверить карточку.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.product-reports.index'))
            ->assertOk()
            ->assertSee('Suspicious product')
            ->assertSee('Неверное описание')
            ->assertSee('Заблокировать товар');

        $this->actingAs($admin)
            ->post(route('admin.product-reports.hide-product', $report), [
                'resolution' => 'Описание нарушает правила.',
            ])
            ->assertRedirect();

        $report->refresh();

        $this->assertSame(ProductReport::STATUS_RESOLVED, $report->status);
        $this->assertSame('product_hidden', $report->action_taken);
        $this->assertSame($admin->id, $report->reviewed_by);
        $this->assertSame(Product::STATUS_BLOCKED, $product->fresh()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $buyer->id,
            'type' => 'product_report_product_hidden',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $seller->id,
            'type' => 'product_hidden_by_report',
            'title' => 'Товар скрыт администратором',
            'url' => route('seller.products.edit', $product, false),
        ]);

        $this->assertStringContainsString(
            'вернуть товар в продажу сможет администратор',
            UserNotification::where('user_id', $seller->id)->where('type', 'product_hidden_by_report')->firstOrFail()->body
        );
    }

    public function test_seller_cannot_restore_admin_blocked_product_and_gets_notice_when_unblocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $category = Category::factory()->create();
        $country = Country::create(['name' => 'Moldova']);
        $city = City::create(['country_id' => $country->id, 'name' => 'Chisinau']);
        $product = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'title' => 'Blocked product',
            'slug' => 'blocked-product',
            'price' => 100,
            'stock' => 3,
            'status' => Product::STATUS_BLOCKED,
            'description' => 'Needs correction',
        ]);

        $sellerPayload = [
            'title' => 'Blocked product fixed',
            'price' => 120,
            'stock' => 4,
            'description' => 'Seller fixed description',
            'status' => Product::STATUS_ACTIVE,
            'category_id' => $category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'currency_base' => 'MDL',
            'price_prb' => 120,
            'price_mdl' => 120,
            'price_uah' => 120,
        ];

        $this->actingAs($seller)
            ->put(route('seller.products.update', $product), $sellerPayload)
            ->assertRedirect(route('seller.products.index'));

        $this->assertSame(Product::STATUS_BLOCKED, $product->fresh()->status);

        $this->actingAs($admin)
            ->put(route('admin.products.update', $product), [
                'title' => $product->title,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'price' => 120,
                'stock' => 4,
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'country_id' => $country->id,
                'city_id' => $city->id,
                'description' => 'Admin checked and restored',
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame(Product::STATUS_ACTIVE, $product->fresh()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $seller->id,
            'type' => 'product_unblocked_by_admin',
            'title' => 'Продажи товара возобновлены',
            'url' => route('seller.products.edit', $product, false),
        ]);
    }

    public function test_admin_can_restore_blocked_product_from_report_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Fixed product after report',
            'slug' => 'fixed-product-after-report',
            'price' => 100,
            'stock' => 3,
            'status' => Product::STATUS_BLOCKED,
        ]);
        $report = ProductReport::create([
            'product_id' => $product->id,
            'user_id' => $buyer->id,
            'reason' => 'Запрещённый товар',
            'details' => 'Нужно проверить карточку.',
            'status' => ProductReport::STATUS_RESOLVED,
            'action_taken' => ProductReport::ACTION_PRODUCT_HIDDEN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.product-reports.index', ['status' => 'all', 'product_status' => Product::STATUS_BLOCKED]))
            ->assertOk()
            ->assertSee('Fixed product after report')
            ->assertSee('Вернуть товар');

        $this->actingAs($admin)
            ->post(route('admin.product-reports.restore-product', $report), [
                'resolution' => 'Продавец исправил карточку, можно вернуть.',
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $report->refresh();

        $this->assertSame(Product::STATUS_ACTIVE, $product->fresh()->status);
        $this->assertSame(ProductReport::STATUS_RESOLVED, $report->status);
        $this->assertSame(ProductReport::ACTION_PRODUCT_RESTORED, $report->action_taken);
        $this->assertSame($admin->id, $report->reviewed_by);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $seller->id,
            'type' => 'product_unblocked_by_admin',
            'title' => 'Продажи товара возобновлены',
            'url' => route('seller.products.edit', $product, false),
        ]);
    }

    public function test_admin_product_report_focus_shows_repeated_products_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $repeatedProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Repeated report product',
            'slug' => 'repeated-report-product',
            'price' => 100,
            'stock' => 3,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $singleProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Single report product',
            'slug' => 'single-report-product',
            'price' => 100,
            'stock' => 3,
            'status' => Product::STATUS_ACTIVE,
        ]);

        ProductReport::create([
            'product_id' => $repeatedProduct->id,
            'user_id' => $buyer->id,
            'reason' => 'Первая жалоба',
        ]);
        ProductReport::create([
            'product_id' => $repeatedProduct->id,
            'user_id' => $buyer->id,
            'reason' => 'Вторая жалоба',
        ]);
        ProductReport::create([
            'product_id' => $singleProduct->id,
            'user_id' => $buyer->id,
            'reason' => 'Одиночная жалоба',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.product-reports.index', ['focus' => 'repeated', 'status' => 'all']))
            ->assertOk()
            ->assertSee('Активный фокус')
            ->assertSee('Повторные жалобы')
            ->assertSee('Repeated report product')
            ->assertSee('2 жалобы на товар')
            ->assertDontSee('Single report product');
    }

    public function test_buyer_can_view_own_disputes_index(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-DISPUTE-LIST',
            'status' => Order::STATUS_PROCESSING,
            'total_price' => 150,
            'currency' => 'PRB',
        ]);
        OrderDispute::create([
            'order_id' => $order->id,
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'reason' => 'Товар не соответствует описанию',
            'details' => 'Нужно проверить заказ.',
        ]);

        $this->actingAs($buyer)
            ->get(route('disputes.index'))
            ->assertOk()
            ->assertSee('Мои обращения и споры')
            ->assertSee('ORD-DISPUTE-LIST')
            ->assertSee('Товар не соответствует описанию');
    }

    public function test_admin_dashboard_and_production_checklist_show_release_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Конкретные задачи для проверки')
            ->assertSee('Релиз-чеклист');

        $this->actingAs($admin)
            ->get(route('admin.production-checklist'))
            ->assertOk()
            ->assertSee('Production checklist')
            ->assertSee('APP_DEBUG=false')
            ->assertSee('Бэкапы БД');
    }

    public function test_seller_products_index_explains_admin_blocked_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Product::create([
            'user_id' => $seller->id,
            'title' => 'Admin blocked seller product',
            'slug' => 'admin-blocked-seller-product',
            'price' => 100,
            'stock' => 2,
            'status' => Product::STATUS_BLOCKED,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.products.index'))
            ->assertOk()
            ->assertSee('Есть товары, заблокированные администратором')
            ->assertSee('самостоятельно опубликовать нельзя');
    }

    public function test_admin_orders_attention_focus_shows_only_actionable_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $stuckOrder = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-STUCK-UX',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'PRB',
        ]);
        $stuckOrder->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        $normalOrder = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-NORMAL-UX',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'PRB',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['focus' => 'attention']))
            ->assertOk()
            ->assertSee('Требуют внимания')
            ->assertSee('Цифра в левом меню ведёт сюда')
            ->assertSee($stuckOrder->number)
            ->assertDontSee($normalOrder->number);
    }

    public function test_admin_can_resolve_order_disputes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-ADMIN-DISPUTE',
            'status' => Order::STATUS_SHIPPED,
            'total_price' => 250,
            'currency' => 'PRB',
        ]);
        $dispute = OrderDispute::create([
            'order_id' => $order->id,
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'reason' => 'Товар повреждён',
            'details' => 'Нужно решение поддержки.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.disputes.index'))
            ->assertOk()
            ->assertSee('ORD-ADMIN-DISPUTE')
            ->assertSee('Товар повреждён');

        $this->actingAs($admin)
            ->post(route('admin.disputes.resolve', $dispute), [
                'resolution' => 'Продавцу нужно связаться с покупателем и согласовать замену.',
            ])
            ->assertRedirect();

        $dispute->refresh();

        $this->assertSame(OrderDispute::STATUS_RESOLVED, $dispute->status);
        $this->assertSame($admin->id, $dispute->resolved_by);
        $this->assertNotNull($dispute->resolved_at);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $buyer->id,
            'type' => 'order_dispute_resolved',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $seller->id,
            'type' => 'order_dispute_resolved',
        ]);
    }

    public function test_email_notification_is_sent_when_user_enabled_email_preferences(): void
    {
        Notification::fake();

        $buyer = User::factory()->create([
            'role' => 'buyer',
            'notification_preferences' => ['email_orders' => true],
        ]);

        app(\App\Services\UserNotificationService::class)->create(
            $buyer,
            'order_status_changed',
            'Статус заказа изменён',
            'Ваш заказ обновлён.',
            '/orders'
        );

        Notification::assertSentTo($buyer, MarketplaceEventNotification::class);
    }

    public function test_public_faq_page_and_sitemap_include_help_links(): void
    {
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee('Вопросы и ответы')
            ->assertSee('Для покупателей')
            ->assertSee('Для продавцов')
            ->assertSee('Является ли оплата картой безопасной транзакцией?')
            ->assertSee('Как начать продавать на WebVitrina?');

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('faq'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('contacts'), false)
            ->assertSee(route('about'), false);
    }

    public function test_legal_documents_are_public_and_substantial(): void
    {
        $documents = [
            ['route' => 'legal.rules', 'text' => 'Правила площадки'],
            ['route' => 'legal.privacy', 'text' => 'Политика конфиденциальности'],
            ['route' => 'legal.delivery-returns', 'text' => 'Доставка, отмены и возвраты'],
            ['route' => 'legal.seller-terms', 'text' => 'Условия для продавцов'],
        ];

        foreach ($documents as $document) {
            $this->get(route($document['route']))
                ->assertOk()
                ->assertSee($document['text'])
                ->assertSee('Дата редакции')
                ->assertSee('03.06.2026');
        }
    }

    public function test_public_about_and_contacts_pages_are_available(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('О WebVitrina')
            ->assertSee('Маркетплейс для локальной торговли');

        $this->get(route('contacts'))
            ->assertOk()
            ->assertSee('Связаться с WebVitrina')
            ->assertSee('+373 (777) 14272');
    }
}
