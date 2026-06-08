<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDispute;
use App\Models\AdCampaign;
use App\Models\AdSlot;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\MarketplaceEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    public function test_notification_redirects_only_to_internal_urls(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $external = UserNotification::create([
            'user_id' => $buyer->id,
            'type' => 'test',
            'title' => 'External',
            'url' => 'https://evil.example/phishing',
        ]);
        $internal = app(\App\Services\UserNotificationService::class)->create(
            $buyer,
            'test',
            'Internal',
            null,
            route('orders.index')
        );
        $stripped = app(\App\Services\UserNotificationService::class)->create(
            $buyer,
            'test',
            'External stripped',
            null,
            'https://evil.example/phishing'
        );

        $this->actingAs($buyer)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $external))
            ->assertRedirect(route('notifications.index'));

        $this->actingAs($buyer)
            ->post(route('notifications.read', $internal))
            ->assertRedirect(route('orders.index', [], false));

        $this->assertNull($stripped->fresh()->url);
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

        $response = $this->actingAs($admin)
            ->get(route('admin.production-checklist'))
            ->assertOk()
            ->assertSee('Production checklist')
            ->assertSee('APP_DEBUG=false')
            ->assertSee('требует внимания')
            ->assertSee('Бэкапы БД')
            ->assertSee('База данных')
            ->assertSee('Диск')
            ->assertSee('Свободно')
            ->assertSee('laravel.log')
            ->assertSee('Размер')
            ->assertSee('Storage')
            ->assertSee('SMTP')
            ->assertSee('Robots')
            ->assertSee('Ошибки 24ч')
            ->assertSee('Финальный контроль')
            ->assertSee('href="#release-infrastructure"', false)
            ->assertSee('href="' . route('sitemap') . '"', false)
            ->assertSee('href="' . route('robots') . '"', false);

        $publicStorage = public_path('storage');
        if (file_exists($publicStorage) && (is_link($publicStorage) || @readlink($publicStorage) !== false)) {
            $response
                ->assertSee('linked')
                ->assertDontSee('Сейчас: нет ссылки');
        }
    }

    public function test_queue_health_check_rejects_sync_without_explicit_local_override(): void
    {
        config(['queue.default' => 'sync']);

        $this->artisan('queue:health-check')
            ->expectsOutput('QUEUE_CONNECTION=sync. This does not verify a real worker.')
            ->assertFailed();
    }

    public function test_queue_health_check_can_run_with_explicit_sync_override_for_local_debugging(): void
    {
        config(['queue.default' => 'sync']);

        $this->artisan('queue:health-check --allow-sync --timeout=1')
            ->expectsOutput('Queue worker processed the health-check job.')
            ->assertSuccessful();
    }

    public function test_backup_health_check_accepts_fresh_complete_backup_with_checksums(): void
    {
        $backupRoot = storage_path('framework/testing/backups-ok');
        $backupDir = $backupRoot . DIRECTORY_SEPARATOR . '20260605-030000';

        File::deleteDirectory($backupRoot);
        File::ensureDirectoryExists($backupDir);

        file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'database.sql.gz', 'database');
        file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'storage-public.tar.gz', 'storage');
        file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'SHA256SUMS', implode(PHP_EOL, [
            hash_file('sha256', $backupDir . DIRECTORY_SEPARATOR . 'database.sql.gz') . ' database.sql.gz',
            hash_file('sha256', $backupDir . DIRECTORY_SEPARATOR . 'storage-public.tar.gz') . ' storage-public.tar.gz',
        ]));

        try {
            $this->artisan('backup:health-check --path=' . str_replace('\\', '/', $backupRoot) . ' --max-age-hours=48')
                ->expectsOutput('Backup health-check passed.')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($backupRoot);
        }
    }

    public function test_backup_health_check_rejects_broken_checksums(): void
    {
        $backupRoot = storage_path('framework/testing/backups-broken');
        $backupDir = $backupRoot . DIRECTORY_SEPARATOR . '20260605-030000';

        File::deleteDirectory($backupRoot);
        File::ensureDirectoryExists($backupDir);

        file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'database.sql.gz', 'database');
        file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'storage-public.tar.gz', 'storage');
        file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'SHA256SUMS', implode(PHP_EOL, [
            str_repeat('0', 64) . ' database.sql.gz',
            hash_file('sha256', $backupDir . DIRECTORY_SEPARATOR . 'storage-public.tar.gz') . ' storage-public.tar.gz',
        ]));

        try {
            $this->artisan('backup:health-check --path=' . str_replace('\\', '/', $backupRoot) . ' --max-age-hours=48')
                ->expectsOutput('Backup health-check failed.')
                ->assertFailed();
        } finally {
            File::deleteDirectory($backupRoot);
        }
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

    public function test_seller_and_admin_product_lists_can_filter_discounted_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $discounted = Product::create([
            'user_id' => $seller->id,
            'title' => 'Release discounted product',
            'slug' => 'release-discounted-product',
            'price' => 80,
            'old_price' => 120,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);
        Product::create([
            'user_id' => $seller->id,
            'title' => 'Regular product without discount',
            'slug' => 'regular-product-without-discount',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($seller)
            ->get(route('seller.products.index', ['discount' => 1]))
            ->assertOk()
            ->assertSee('Со скидкой')
            ->assertSee($discounted->title)
            ->assertDontSee('Regular product without discount');

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['discount' => 1]))
            ->assertOk()
            ->assertSee('Со скидкой')
            ->assertSee($discounted->title)
            ->assertDontSee('Regular product without discount');
    }

    public function test_buyer_order_page_shows_one_clear_next_action(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Actionable order product',
            'slug' => 'actionable-order-product',
            'price' => 100,
            'stock' => 3,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-NEXT-ACTION',
            'status' => Order::STATUS_SHIPPED,
            'total_price' => 100,
            'currency' => 'PRB',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Что делать дальше')
            ->assertSee('Проверьте получение товара')
            ->assertSee('Подтвердить получение');
    }

    public function test_admin_orders_attention_focus_shows_only_cancellation_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $cancelRequestOrder = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-CANCEL-REQUEST-UX',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'PRB',
            'cancellation_requested_at' => now(),
            'cancellation_reason' => 'Покупатель передумал.',
        ]);

        $slowOrder = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-SLOW-BUT-NOT-URGENT-UX',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'PRB',
        ]);
        $slowOrder->forceFill([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['focus' => 'attention']))
            ->assertOk()
            ->assertSee('Запросы отмены')
            ->assertSee('Красная карточка и цифра в левом меню показывают запросы отмены')
            ->assertSee($cancelRequestOrder->number)
            ->assertDontSee($slowOrder->number);
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
                ->assertSee('06.06.2026');
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
            ->assertSee('+373 (778) 64495');
    }

    public function test_product_and_category_pages_include_seo_metadata(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create([
            'user_id' => $seller->id,
            'name' => 'SEO Shop',
            'description' => 'Shop for SEO checks',
        ]);
        $category = Category::factory()->create([
            'name' => 'SEO Category',
            'slug' => 'seo-category',
        ]);
        $product = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'SEO Product',
            'slug' => 'seo-product',
            'description' => 'SEO product description for public metadata.',
            'price' => 100,
            'stock' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="' . route('product.show', $product->slug) . '"', false)
            ->assertSee('<meta property="og:type" content="product"', false)
            ->assertSee('"@type": "Product"', false);

        $this->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="' . route('category.show', $category->slug) . '"', false)
            ->assertSee('<meta property="og:type" content="website"', false)
            ->assertSee('"@type": "CollectionPage"', false);
    }

    public function test_home_catalog_uses_load_more_button_for_public_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create([
            'user_id' => $seller->id,
            'name' => 'Home Load More Shop',
            'slug' => 'home-load-more-shop',
        ]);

        foreach (range(1, 21) as $number) {
            Product::create([
                'user_id' => $seller->id,
                'title' => 'Home load more product ' . $number,
                'slug' => 'home-load-more-product-' . $number,
                'price' => 100 + $number,
                'stock' => 5,
                'status' => Product::STATUS_ACTIVE,
            ]);
        }

        cache()->forget('ads.home');
        cache()->forget('products.home.high_rating_recommendations');
        cache()->forget('products.home.catalog_recommendations');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-load-more-root="home-products"', false)
            ->assertSee('data-load-more-button', false)
            ->assertSee('Показать ещё')
            ->assertDontSee('Показано');
    }

    public function test_category_catalog_uses_load_more_button_for_public_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create([
            'user_id' => $seller->id,
            'name' => 'Category Load More Shop',
            'slug' => 'category-load-more-shop',
        ]);
        $category = Category::factory()->create([
            'name' => 'Load More Category',
            'slug' => 'load-more-category',
        ]);

        foreach (range(1, 21) as $number) {
            Product::create([
                'user_id' => $seller->id,
                'category_id' => $category->id,
                'title' => 'Category load more product ' . $number,
                'slug' => 'category-load-more-product-' . $number,
                'price' => 100 + $number,
                'stock' => 5,
                'status' => Product::STATUS_ACTIVE,
            ]);
        }

        cache()->forget('ads.category.featured');

        $this->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('data-load-more-root="category-products"', false)
            ->assertSee('data-load-more-button', false)
            ->assertSee('Показать ещё')
            ->assertDontSee('Показано');
    }

    public function test_category_index_has_compact_category_cards_without_product_sorting(): void
    {
        Category::factory()->create([
            'name' => 'Compact Category',
            'slug' => 'compact-category',
        ]);

        $this->get(route('category.index'))
            ->assertOk()
            ->assertSee('Все категории')
            ->assertSee('Compact Category')
            ->assertSee('data-category-media', false)
            ->assertDontSee('По популярности');
    }

    public function test_header_category_drawer_shows_root_categories_and_children(): void
    {
        $parent = Category::factory()->create([
            'name' => 'Drawer Parent Category',
            'slug' => 'drawer-parent-category',
        ]);
        Category::factory()->create([
            'name' => 'Drawer Child Category',
            'slug' => 'drawer-child-category',
            'parent_id' => $parent->id,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Быстрый переход по разделам WebVitrina')
            ->assertSee(route('category.index'), false)
            ->assertSee('Drawer Parent Category')
            ->assertSee('Drawer Child Category')
            ->assertSee('Перейти в категорию Drawer Parent Category')
            ->assertSee('1 подразделов')
            ->assertSee('category-menu-close', false);
    }

    public function test_admin_sees_public_mobile_bottom_nav_on_marketplace_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-mobile-bottom-nav', false);
    }

    public function test_public_seller_shop_uses_load_more_button_for_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Seller Load More Shop',
            'slug' => 'seller-load-more-shop',
        ]);

        foreach (range(1, 21) as $number) {
            Product::create([
                'user_id' => $seller->id,
                'title' => 'Seller load more product ' . $number,
                'slug' => 'seller-load-more-product-' . $number,
                'price' => 100 + $number,
                'stock' => 5,
                'status' => Product::STATUS_ACTIVE,
            ]);
        }

        $this->get(route('seller.show', $shop->slug))
            ->assertOk()
            ->assertSee('data-load-more-root="seller-products"', false)
            ->assertSee('data-load-more-button', false)
            ->assertSee('Показать ещё')
            ->assertDontSee('Показано');
    }

    public function test_public_seller_shop_filters_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Filter Shop',
            'description' => 'Seller catalog',
        ]);

        $oldProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Old regular item',
            'slug' => 'old-regular-item',
            'price' => 100,
            'stock' => 3,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $oldProduct->forceFill([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ])->save();

        $newProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Fresh new item',
            'slug' => 'fresh-new-item',
            'price' => 120,
            'stock' => 4,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $newProduct->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        $hitProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Popular hit item',
            'slug' => 'popular-hit-item',
            'price' => 140,
            'stock' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $hitProduct->forceFill([
            'views_count' => 25,
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ])->save();

        $saleProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Discount sale item',
            'slug' => 'discount-sale-item',
            'price' => 90,
            'old_price' => 120,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $saleProduct->forceFill([
            'created_at' => now()->subDays(35),
            'updated_at' => now()->subDays(35),
        ])->save();

        $this->get(route('seller.show', ['identifier' => $shop->slug, 'filter' => 'new']))
            ->assertOk()
            ->assertSee('Новинки магазина')
            ->assertSee('Fresh new item')
            ->assertDontSee('Old regular item');

        $this->get(route('seller.show', ['identifier' => $shop->slug, 'filter' => 'hit']))
            ->assertOk()
            ->assertSee('Хиты магазина')
            ->assertSee('Popular hit item')
            ->assertDontSee('Old regular item');

        $this->get(route('seller.show', ['identifier' => $shop->slug, 'filter' => 'sale']))
            ->assertOk()
            ->assertSee('Товары со скидкой')
            ->assertSee('Discount sale item')
            ->assertDontSee('Old regular item');
    }

    public function test_public_seller_shop_does_not_show_generic_marketing_footer(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Clean Seller Footer Shop',
            'slug' => 'clean-seller-footer-shop',
        ]);

        Product::create([
            'user_id' => $seller->id,
            'title' => 'Clean footer product',
            'slug' => 'clean-footer-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->get(route('seller.show', $shop->slug))
            ->assertOk()
            ->assertDontSee('Быстрая доставка')
            ->assertDontSee('Гарантия качества')
            ->assertDontSee('Топ продавцов')
            ->assertDontSee('Смотреть товары магазина');
    }

    public function test_admin_can_create_manual_retail_media_campaign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Promo Shop']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Promo product',
            'slug' => 'promo-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $slot = AdSlot::where('key', AdSlot::HOME_FEATURED_PRODUCTS)->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.ads.index'))
            ->assertOk()
            ->assertSee('Реклама / Продвижение')
            ->assertSee('Обзор')
            ->assertSee('Кампании')
            ->assertSee('Статистика')
            ->assertDontSee('Слоты')
            ->assertSee('Рекламные места')
            ->assertSee('Популярное в категории')
            ->assertSee('Добавить кампанию');

        $this->actingAs($admin)
            ->post(route('admin.ads.store'), [
                'ad_slot_id' => $slot->id,
                'target_type' => AdCampaign::TYPE_PRODUCT,
                'product_id' => $product->id,
                'title' => 'Promo campaign',
                'label' => 'Продвигается',
                'sort_order' => 10,
                'max_impressions' => 500,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.ads.index'));

        $this->assertDatabaseHas('ad_campaigns', [
            'ad_slot_id' => $slot->id,
            'product_id' => $product->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Promo campaign',
            'label' => 'Продвигается',
            'is_active' => true,
            'max_impressions' => 500,
            'created_by' => $admin->id,
        ]);
    }

    public function test_home_page_shows_promoted_campaigns_with_visible_label(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Visible Promo Shop']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Visible promoted product',
            'slug' => 'visible-promoted-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        AdCampaign::create([
            'ad_slot_id' => AdSlot::where('key', AdSlot::HOME_FEATURED_PRODUCTS)->firstOrFail()->id,
            'product_id' => $product->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Visible campaign',
            'label' => 'Продвигается',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        cache()->forget('ads.home');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Рекомендуемые товары')
            ->assertSee('Продвигается')
            ->assertSee('Visible promoted product');
    }

    public function test_home_promoted_products_use_higher_priority_first(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Priority Promo Shop']);
        $slot = AdSlot::where('key', AdSlot::HOME_FEATURED_PRODUCTS)->firstOrFail();

        $lowPriorityProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'Low priority promoted product',
            'slug' => 'low-priority-promoted-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $highPriorityProduct = Product::create([
            'user_id' => $seller->id,
            'title' => 'High priority promoted product',
            'slug' => 'high-priority-promoted-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        AdCampaign::create([
            'ad_slot_id' => $slot->id,
            'product_id' => $lowPriorityProduct->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Low priority campaign',
            'label' => 'Продвигается',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        AdCampaign::create([
            'ad_slot_id' => $slot->id,
            'product_id' => $highPriorityProduct->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'High priority campaign',
            'label' => 'Продвигается',
            'sort_order' => 200,
            'is_active' => true,
        ]);
        cache()->forget('ads.home');

        $content = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $highPosition = strpos($content, 'High priority promoted product');
        $lowPosition = strpos($content, 'Low priority promoted product');

        $this->assertNotFalse($highPosition);
        $this->assertNotFalse($lowPosition);
        $this->assertLessThan($lowPosition, $highPosition);
    }

    public function test_home_recommendations_link_to_dedicated_page_when_many_items_exist(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Many Recommendations Shop']);
        $slot = AdSlot::where('key', AdSlot::HOME_FEATURED_PRODUCTS)->firstOrFail();

        foreach (range(1, 7) as $number) {
            $product = Product::create([
                'user_id' => $seller->id,
                'title' => 'Many recommendation product ' . $number,
                'slug' => 'many-recommendation-product-' . $number,
                'price' => 100 + $number,
                'stock' => 5,
                'status' => Product::STATUS_ACTIVE,
            ]);

            AdCampaign::create([
                'ad_slot_id' => $slot->id,
                'product_id' => $product->id,
                'target_type' => AdCampaign::TYPE_PRODUCT,
                'title' => 'Many recommendation campaign ' . $number,
                'label' => 'Продвигается',
                'sort_order' => 100 - $number,
                'is_active' => true,
            ]);
        }
        cache()->forget('ads.home');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Рекомендуемые товары')
            ->assertSee('Все рекомендации')
            ->assertSee(route('recommendations.index'), false);

        $this->get(route('recommendations.index'))
            ->assertOk()
            ->assertSee('Рекомендованные товары')
            ->assertSee('Many recommendation product 7');
    }

    public function test_home_page_fills_recommended_products_with_high_rated_items(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Rated Seller Shop']);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Highly rated fallback product',
            'slug' => 'highly-rated-fallback-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        foreach ([5, 5, 4] as $index => $rating) {
            Review::create([
                'user_id' => User::factory()->create(['role' => 'buyer'])->id,
                'product_id' => $product->id,
                'rating' => $rating,
                'body' => 'Approved rating ' . $index,
                'status' => Review::STATUS_APPROVED,
            ]);
        }

        cache()->forget('ads.home');
        cache()->forget('products.home.high_rating_recommendations');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Рекомендуемые товары')
            ->assertSee('Высокий рейтинг')
            ->assertSee('Highly rated fallback product');
    }

    public function test_home_page_fills_recommended_products_with_catalog_items_when_reviews_are_missing(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Catalog Seller Shop']);

        foreach (range(1, 4) as $number) {
            Product::create([
                'user_id' => $seller->id,
                'title' => 'Catalog fallback product ' . $number,
                'slug' => 'catalog-fallback-product-' . $number,
                'price' => 100 + $number,
                'stock' => 5,
                'status' => Product::STATUS_ACTIVE,
            ]);
        }

        cache()->forget('ads.home');
        cache()->forget('products.home.high_rating_recommendations');
        cache()->forget('products.home.catalog_recommendations');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Рекомендуемые товары')
            ->assertSee('Catalog fallback product 4')
            ->assertSee('Catalog fallback product 3');
    }

    public function test_admin_ad_form_searches_products_and_shops(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Needle Search Shop',
            'slug' => 'needle-search-shop',
        ]);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Needle Search Product',
            'slug' => 'needle-search-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.ads.search.products', ['q' => 'Needle Search Product']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $product->id,
                'title' => '#' . $product->id . ' · Needle Search Product',
            ]);

        $this->actingAs($admin)
            ->getJson(route('admin.ads.search.shops', ['q' => 'Needle Search Shop']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $shop->id,
                'title' => '#' . $shop->id . ' · Needle Search Shop',
            ]);
    }

    public function test_shop_banner_url_falls_back_when_file_is_missing(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Missing Banner Shop',
            'slug' => 'missing-banner-shop',
            'banner' => 'banners/medium/missing.webp',
        ]);

        $this->assertSame(asset('images/default-shop-banner.jpg'), $shop->banner_url);
    }

    public function test_shop_update_clears_retail_media_cache(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Cached Banner Shop',
            'slug' => 'cached-banner-shop',
            'banner' => 'banners/medium/old.webp',
        ]);

        Cache::put('ads.home', collect(['stale']), 300);
        Cache::put('ads.category.featured', collect(['stale']), 300);

        $shop->update(['banner' => 'banners/medium/new.webp']);

        $this->assertFalse(Cache::has('ads.home'));
        $this->assertFalse(Cache::has('ads.category.featured'));
    }

    public function test_single_weekly_shop_ad_uses_shop_banner_as_wide_card(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/medium/seller.webp', 'avatar');
        Storage::disk('public')->put('banners/medium/shop.webp', 'banner');

        $seller = User::factory()->create([
            'role' => 'seller',
            'name' => 'Avatar Seller',
            'avatar' => 'avatars/medium/seller.webp',
        ]);
        $shop = Shop::create([
            'user_id' => $seller->id,
            'name' => 'Avatar Promo Shop',
            'slug' => 'avatar-promo-shop',
            'banner' => 'banners/medium/shop.webp',
        ]);

        AdCampaign::create([
            'ad_slot_id' => AdSlot::where('key', AdSlot::HOME_WEEKLY_SHOPS)->firstOrFail()->id,
            'shop_id' => $shop->id,
            'target_type' => AdCampaign::TYPE_SHOP,
            'title' => 'Weekly shop campaign',
            'label' => 'Продвигается',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        cache()->forget('ads.home');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Магазины недели')
            ->assertSee(Storage::url('banners/medium/shop.webp'), false)
            ->assertDontSee(Storage::url('avatars/medium/seller.webp'), false);
    }

    public function test_weekly_shop_carousel_uses_seller_avatar_not_shop_banner(): void
    {
        Storage::fake('public');

        foreach (range(1, 3) as $number) {
            Storage::disk('public')->put("avatars/medium/seller-{$number}.webp", 'avatar');
            Storage::disk('public')->put("banners/medium/shop-{$number}.webp", 'banner');

            $seller = User::factory()->create([
                'role' => 'seller',
                'name' => 'Carousel Seller ' . $number,
                'avatar' => "avatars/medium/seller-{$number}.webp",
            ]);
            $shop = Shop::create([
                'user_id' => $seller->id,
                'name' => 'Carousel Promo Shop ' . $number,
                'slug' => 'carousel-promo-shop-' . $number,
                'banner' => "banners/medium/shop-{$number}.webp",
            ]);

            AdCampaign::create([
                'ad_slot_id' => AdSlot::where('key', AdSlot::HOME_WEEKLY_SHOPS)->firstOrFail()->id,
                'shop_id' => $shop->id,
                'target_type' => AdCampaign::TYPE_SHOP,
                'title' => 'Weekly shop carousel campaign ' . $number,
                'label' => 'Продвигается',
                'sort_order' => $number,
                'is_active' => true,
            ]);
        }

        cache()->forget('ads.home');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('weekly-shop-track', false)
            ->assertSee('weekly-shop-marquee', false)
            ->assertDontSee('aria-label="Следующий магазин"', false)
            ->assertSee(Storage::url('avatars/medium/seller-1.webp'), false)
            ->assertDontSee(Storage::url('banners/medium/shop-1.webp'), false);
    }

    public function test_seller_avatar_update_clears_retail_media_cache(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'avatar' => 'avatars/medium/old.webp',
        ]);
        Shop::create([
            'user_id' => $seller->id,
            'name' => 'Avatar Cache Shop',
            'slug' => 'avatar-cache-shop',
        ]);

        Cache::put('ads.home', collect(['stale']), 300);
        Cache::put('ads.category.featured', collect(['stale']), 300);

        $seller->update(['avatar' => 'avatars/medium/new.webp']);

        $this->assertFalse(Cache::has('ads.home'));
        $this->assertFalse(Cache::has('ads.category.featured'));
    }

    public function test_category_page_shows_featured_ad_slot_with_visible_label(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Category Promo Shop']);
        $category = Category::factory()->create([
            'name' => 'Ad Category',
            'slug' => 'ad-category',
        ]);
        $product = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Category promoted product',
            'slug' => 'category-promoted-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        AdCampaign::create([
            'ad_slot_id' => AdSlot::where('key', AdSlot::CATEGORY_FEATURED_PRODUCTS)->firstOrFail()->id,
            'product_id' => $product->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Category visible campaign',
            'label' => 'Продвигается',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        cache()->forget('ads.category.featured');

        $this->get(route('category.show', $category->slug))
            ->assertOk()
            ->assertSee('Популярное в категории')
            ->assertSee('Продвигается')
            ->assertSee('Category promoted product');
    }

    public function test_category_promoted_products_use_higher_priority_first(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Category Priority Shop']);
        $category = Category::factory()->create([
            'name' => 'Category Priority',
            'slug' => 'category-priority',
        ]);
        $slot = AdSlot::where('key', AdSlot::CATEGORY_FEATURED_PRODUCTS)->firstOrFail();

        $lowPriorityProduct = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Category low priority product',
            'slug' => 'category-low-priority-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $highPriorityProduct = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Category high priority product',
            'slug' => 'category-high-priority-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        AdCampaign::create([
            'ad_slot_id' => $slot->id,
            'category_id' => $category->id,
            'product_id' => $lowPriorityProduct->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Category low priority campaign',
            'label' => 'Продвигается',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        AdCampaign::create([
            'ad_slot_id' => $slot->id,
            'category_id' => $category->id,
            'product_id' => $highPriorityProduct->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Category high priority campaign',
            'label' => 'Продвигается',
            'sort_order' => 200,
            'is_active' => true,
        ]);

        $content = $this->get(route('category.show', $category->slug))
            ->assertOk()
            ->getContent();

        $highPosition = strpos($content, 'Category high priority product');
        $lowPosition = strpos($content, 'Category low priority product');

        $this->assertNotFalse($highPosition);
        $this->assertNotFalse($lowPosition);
        $this->assertLessThan($lowPosition, $highPosition);
    }

    public function test_category_ad_campaign_can_be_limited_to_one_category(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        Shop::create(['user_id' => $seller->id, 'name' => 'Limited Promo Shop']);
        $visibleCategory = Category::factory()->create([
            'name' => 'Visible Ad Category',
            'slug' => 'visible-ad-category',
        ]);
        $otherCategory = Category::factory()->create([
            'name' => 'Other Ad Category',
            'slug' => 'other-ad-category',
        ]);
        $product = Product::create([
            'user_id' => $seller->id,
            'category_id' => $visibleCategory->id,
            'title' => 'Only visible here product',
            'slug' => 'only-visible-here-product',
            'price' => 100,
            'stock' => 5,
            'status' => Product::STATUS_ACTIVE,
        ]);

        AdCampaign::create([
            'ad_slot_id' => AdSlot::where('key', AdSlot::CATEGORY_FEATURED_PRODUCTS)->firstOrFail()->id,
            'category_id' => $visibleCategory->id,
            'product_id' => $product->id,
            'target_type' => AdCampaign::TYPE_PRODUCT,
            'title' => 'Limited category campaign',
            'label' => 'Продвигается',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->get(route('category.show', $visibleCategory->slug))
            ->assertOk()
            ->assertSee('Only visible here product');

        $this->get(route('category.show', $otherCategory->slug))
            ->assertOk()
            ->assertDontSee('Only visible here product');
    }

    public function test_parent_category_page_shows_subcategories_without_product_filters(): void
    {
        $parent = Category::factory()->create([
            'name' => 'Parent visual category',
            'slug' => 'parent-visual-category',
        ]);
        Category::factory()->create([
            'name' => 'Child visual category',
            'slug' => 'child-visual-category',
            'parent_id' => $parent->id,
        ]);

        $this->get(route('category.show', $parent->slug))
            ->assertOk()
            ->assertSee('Child visual category')
            ->assertSee('Выберите раздел')
            ->assertSee('aria-label="Breadcrumbs"', false)
            ->assertSee('hidden h-16 lg:block', false)
            ->assertDontSee('Панель фильтров')
            ->assertDontSee('Популярное в категории');
    }
}
