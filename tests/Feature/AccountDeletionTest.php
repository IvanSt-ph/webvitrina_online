<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserRememberedDevice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_without_orders_is_anonymized_and_personal_files_are_removed(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $buyer = User::factory()->create(['avatar' => 'avatars/deleted.webp', 'phone' => '+37369111111', 'provider' => 'google', 'provider_id' => 'old-google']);
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer->addresses()->create(['city' => 'Private city']);
        $conversation = Conversation::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $conversation->messages()->create(['sender_id' => $buyer->id, 'body' => 'Private text', 'image_path' => 'chat-images/deleted.webp']);
        Storage::disk('public')->put($buyer->avatar, 'avatar');
        Storage::disk('local')->put('chat-images/deleted.webp', 'image');
        DB::table('password_reset_tokens')->insert(['email' => $buyer->email, 'token' => 'old-token', 'created_at' => now()]);

        $this->actingAs($buyer)->delete('/profile', ['password' => 'password'])->assertRedirect('/')->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertSoftDeleted($buyer);
        $deleted = User::withTrashed()->findOrFail($buyer->id);
        foreach (['email', 'phone', 'avatar', 'provider', 'provider_id', 'remember_token', 'password_set_at', 'notification_preferences'] as $field) {
            $this->assertNull($deleted->$field, $field);
        }
        $this->assertSame('Удалённый аккаунт', $deleted->name);
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $buyer->id]);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Storage::disk('public')->assertMissing('avatars/deleted.webp');
        Storage::disk('local')->assertMissing('chat-images/deleted.webp');
    }

    public function test_all_order_states_items_and_money_survive_self_deletion_and_remain_viewable(): void
    {
        $buyer = User::factory()->create(['name' => 'Original Buyer', 'phone' => '+37369222222']);
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->create(['role' => 'admin']);
        $address = $buyer->addresses()->create(['city' => 'Order city', 'street' => 'Order street']);
        $orders = collect(Order::allStatuses())->map(fn ($status) => $this->order($buyer, $seller, $status, $address->id));
        $before = $orders->mapWithKeys(fn ($order) => [$order->id => [
            'order' => $order->getRawOriginal(),
            'item' => $order->items()->first()->getRawOriginal(),
        ]]);
        $buyer->update(['name' => 'Changed Profile', 'email' => 'changed-profile@example.com']);

        $this->actingAs($buyer)->delete('/profile', ['password' => 'password'])->assertRedirect('/');
        foreach ($orders as $order) {
            $this->assertSame($before[$order->id]['order'], $order->fresh()->getRawOriginal());
            $this->assertSame($before[$order->id]['item'], $order->items()->first()->getRawOriginal());
            $this->assertSame(7, $order->items()->first()->product->stock);
        }
        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
        $completed = $orders->firstWhere('status', Order::STATUS_COMPLETED);
        $this->actingAs($seller)->get(route('seller.orders.show', $completed))->assertOk()->assertSee('Original Buyer')->assertSee('Order street')->assertDontSee('Changed Profile');
        $this->get(route('seller.orders.index'))->assertOk()->assertSee('Original Buyer');
        $this->actingAs($admin)->get(route('admin.orders.show', $completed))->assertOk()->assertSee('Original Buyer');
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('Original Buyer');
        $this->actingAs($seller)->post(route('seller.orders.chat.buyer', $completed))->assertRedirect()->assertSessionHas('error');
        $pending = $orders->firstWhere('status', Order::STATUS_PENDING);
        $this->post(route('seller.orders.updateStatus', $pending), ['status' => Order::STATUS_PROCESSING])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_PROCESSING, $pending->fresh()->status);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $buyer->id]);
    }

    public function test_admin_deletion_preserves_orders_and_repeat_deletion_is_safe(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = $this->order($buyer, $seller);
        $emailHash = hash('sha256', $buyer->email);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $buyer))->assertRedirect(route('admin.users.index'));
        $this->assertSoftDeleted($buyer);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $buyer->id]);
        $meta = json_decode(DB::table('admin_activity_logs')->where('action', 'user.deleted')->sole()->meta, true);
        $this->assertSame($emailHash, $meta['deleted_user_email_hash']);
        $this->delete(route('admin.users.destroy', $buyer))->assertNotFound();
        $this->assertFalse($buyer->delete());
        $this->assertSame(1, $order->items()->count());
    }

    public function test_old_session_remember_cookie_and_remembered_device_cannot_authenticate(): void
    {
        $buyer = User::factory()->create(['remember_token' => 'old-remember-token']);
        $sessionKey = Auth::guard()->getName();
        $recallerName = Auth::guard()->getRecallerName();
        $recaller = $buyer->id.'|'.$buyer->remember_token.'|'.$buyer->password;
        UserRememberedDevice::create(['user_id' => $buyer->id, 'selector' => 'old-selector', 'token_hash' => hash('sha256', 'old-token'), 'expires_at' => now()->addDay()]);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $buyer->id, 'payload' => base64_encode('old'), 'last_activity' => time()]);
        $buyer->delete();
        $this->assertDatabaseMissing('sessions', ['user_id' => $buyer->id]);
        $this->assertDatabaseMissing('user_remembered_devices', ['user_id' => $buyer->id]);
        Auth::forgetGuards();
        $this->withSession([$sessionKey => $buyer->id])->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
        session()->flush();
        Auth::forgetGuards();
        $this->withCookie($recallerName, $recaller)->get('/profile')->assertRedirect(route('login'));
        $this->post(route('login.remembered'), ['selector' => 'old-selector', 'token' => 'old-token'])->assertSessionHasErrors('login');
        $this->delete('/profile', ['password' => 'password'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_same_email_can_register_a_new_account_without_inheriting_old_orders(): void
    {
        Notification::fake();
        $buyer = User::factory()->create(['email' => 'reused@example.com']);
        $order = $this->order($buyer, User::factory()->create(['role' => 'seller']));
        $buyer->delete();
        $this->post('/register', ['name' => 'New Account', 'email' => 'reused@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => 'buyer', 'terms' => '1'])->assertSessionHasNoErrors();
        $this->assertAuthenticated();
        $this->assertNotEquals($buyer->id, Auth::id());
        $this->get(route('orders.show', $order))->assertForbidden();
    }

    public function test_google_login_creates_a_new_identity_and_does_not_restore_old_orders(): void
    {
        Notification::fake();
        $buyer = User::factory()->create(['email' => 'google-deleted@example.com', 'provider' => 'google', 'provider_id' => 'google-deleted']);
        $order = $this->order($buyer, User::factory()->create(['role' => 'seller']));
        $buyer->delete();
        Socialite::fake('google', SocialiteUser::fake(['id' => 'google-deleted', 'name' => 'Google User', 'email' => 'google-deleted@example.com'])->setRaw(['email_verified' => true]));
        $this->get(route('auth.google.callback'))->assertRedirect();
        $this->assertAuthenticated();
        $this->assertNotEquals($buyer->id, Auth::id());
        $this->assertSame($buyer->id, $order->fresh()->user_id);
        $this->assertSoftDeleted($buyer);
    }

    public function test_seller_deletion_withdraws_catalogue_but_keeps_orders_and_stock(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->create(['role' => 'admin']);
        $shop = Shop::create(['user_id' => $seller->id, 'name' => 'Private Shop', 'phone' => '+37369000000']);
        $order = $this->order($buyer, $seller);
        $unsold = Product::create(['user_id' => $seller->id, 'title' => 'Unsold', 'slug' => 'unsold-delete', 'price' => 10, 'stock' => 2, 'image' => 'products/unsold.webp', 'gallery' => ['products/unsold-gallery.webp']]);
        Storage::disk('public')->put($unsold->image, 'image');
        Storage::disk('public')->put('products/unsold-gallery.webp', 'gallery');
        $seller->delete();
        $this->assertDatabaseMissing('products', ['id' => $unsold->id]);
        Storage::disk('public')->assertMissing('products/unsold.webp');
        Storage::disk('public')->assertMissing('products/unsold-gallery.webp');
        $this->assertDatabaseMissing('shops', ['id' => $shop->id]);
        $this->assertSame(7, $order->items()->first()->product->stock);
        $this->assertSame(Product::STATUS_BLOCKED, $order->items()->first()->product->status);
        $this->assertSame($seller->id, $order->fresh()->seller_id);
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk();
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
    }

    public function test_database_foreign_keys_reject_physical_deletion_of_order_participants(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $order = $this->order($buyer, $seller);
        foreach ([$buyer, $seller] as $user) {
            try {
                DB::table('users')->where('id', $user->id)->delete();
                $this->fail('Physical participant deletion must be rejected.');
            } catch (QueryException $exception) {
                $this->assertSame('23000', $exception->errorInfo[0]);
            }
        }
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertSame(1, $order->items()->count());
    }

    private function order(User $buyer, User $seller, string $status = Order::STATUS_PENDING, ?int $addressId = null): Order
    {
        $product = Product::create(['user_id' => $seller->id, 'title' => 'History product', 'slug' => 'history-'.uniqid(), 'price' => 100, 'stock' => 7, 'status' => Product::STATUS_ACTIVE]);
        $order = Order::create(['user_id' => $buyer->id, 'seller_id' => $seller->id, 'address_id' => $addressId, 'number' => Order::generateNumber(), 'status' => $status, 'currency' => 'MDL', 'total_price' => 50, 'payment_method' => 'cash', 'delivery_method' => 'pickup']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 50, 'total' => 50, 'source_price' => 100, 'source_currency' => 'PRB', 'exchange_rate' => 0.5]);

        return $order->refresh();
    }
}
