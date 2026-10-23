<?php

namespace Tests\Support;

use App\Database\SafeDatabaseTransactionsManager;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class CheckoutTestCase extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(SafeDatabaseTransactionsManager::class, app('db.transactions')::class);
        $property = new \ReflectionProperty(DB::connection(), 'transactionsManager');
        $this->assertSame(app('db.transactions'), $property->getValue(DB::connection()));
        $this->rollBackTestTransactionsBeforeApplicationDestroyed();
    }

    protected function prepareCart(int $sellerCount = 1, bool $email = false): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $sellers = [];
        for ($i = 0; $i < $sellerCount; $i++) {
            $seller = User::factory()->create([
                'role' => 'seller', 'notification_preferences' => ['email_orders' => $email, 'site_orders' => false],
            ]);
            $sellers[] = $seller;
            $product = Product::create([
                'user_id' => $seller->id, 'title' => 'Notification product '.$i,
                'slug' => 'notification-product-'.$i, 'price' => 100, 'currency_base' => 'PRB',
                'stock' => 5, 'status' => Product::STATUS_ACTIVE,
            ]);
            CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'qty' => 1]);
        }
        $this->actingAs($buyer)->withSession(['currency' => 'PRB'])->post(route('checkout.prepare'))->assertRedirect();
        $this->get(route('checkout.confirm'))->assertOk();

        return [$buyer, $sellers];
    }

    protected function submit()
    {
        return $this->post(route('checkout.create'), [
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'checkout_token' => session('checkout_token'),
        ]);
    }
}
