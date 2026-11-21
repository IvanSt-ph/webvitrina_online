<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PhoneAssignmentService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Support\CheckoutTestCase;
use Tests\Support\ProductQuotaFixtures;

class SellerPhoneAccessTest extends CheckoutTestCase
{
    use ProductQuotaFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        config(['backup.lock_path' => stream_get_meta_data($lock)['uri'],
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array']);
        Notification::fake();
    }

    public function test_buyer_without_phone_can_buy_from_verified_seller(): void
    {
        [$buyer] = $this->prepareCart();
        $this->assertNull($buyer->phone);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertSame(1, Order::where('user_id', $buyer->id)->count());
    }

    public function test_phone_change_after_checkout_preparation_blocks_order_and_stock_write(): void
    {
        [$buyer, $sellers] = $this->prepareCart();
        $seller = $sellers[0];
        app(PhoneAssignmentService::class)->assignToUser($seller, '+37377123456');
        $this->submit()->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::where('user_id', $buyer->id)->count());
        $this->assertSame(5, (int) Product::where('user_id', $seller->id)->value('stock'));
    }

    public function test_unverified_seller_can_save_draft_but_cannot_publish_it(): void
    {
        [$seller, $data] = $this->quotaFixture(0);
        $seller->forceFill(['phone_verified_at' => null])->save();
        $data['status'] = Product::STATUS_DRAFT;
        $product = app(ProductService::class)->create($data);
        try {
            app(ProductService::class)->update($product, ['status' => Product::STATUS_ACTIVE], actor: $seller);
            $this->fail('Publication must require a verified personal phone.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
        $this->assertSame(Product::STATUS_DRAFT, $product->fresh()->status);
    }

    public function test_verified_shop_phone_does_not_authorize_personal_phone_publication(): void
    {
        [$seller, $data] = $this->quotaFixture(0);
        $seller->forceFill(['phone_verified_at' => null])->save();
        $seller->shop()->create(['name' => 'Verified shop', 'phone' => $seller->phone,
            'phone_verified_at' => now()]);
        $this->expectException(ValidationException::class);
        app(ProductService::class)->create($data);
    }

    public function test_phone_verification_does_not_replace_email_verification(): void
    {
        [$seller, $data] = $this->quotaFixture(0);
        $seller->forceFill(['email_verified_at' => null])->save();
        $this->expectException(ValidationException::class);
        app(ProductService::class)->create($data);
    }

    public function test_admin_cannot_publish_for_unverified_seller(): void
    {
        [$seller, $data] = $this->quotaFixture(0);
        $seller->forceFill(['phone_verified_at' => null])->save();
        $data['status'] = Product::STATUS_DRAFT;
        $product = app(ProductService::class)->create($data);
        $admin = User::factory()->admin()->create();
        $this->expectException(ValidationException::class);
        app(ProductService::class)->update($product, ['status' => Product::STATUS_ACTIVE], actor: $admin);
    }

    public function test_existing_verified_personal_phone_allows_publication_without_telegram_history(): void
    {
        [$seller, $data] = $this->quotaFixture(0);
        $verifiedAt = $seller->phone_verified_at;
        $product = app(ProductService::class)->create($data);
        $this->assertSame(Product::STATUS_ACTIVE, $product->status);
        $this->assertTrue($verifiedAt->equalTo($seller->fresh()->phone_verified_at));
    }
}
