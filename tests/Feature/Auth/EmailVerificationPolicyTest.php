<?php

namespace Tests\Feature\Auth;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class EmailVerificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_can_login_open_verification_and_resend_with_throttling(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->get(route('verification.notice'))->assertOk();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('verification.send'))->assertRedirect();
        }

        $this->post(route('verification.send'))->assertTooManyRequests();
    }

    public function test_unverified_user_can_use_cart_and_favorites(): void
    {
        $buyer = User::factory()->unverified()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $product))
            ->assertOk()
            ->assertJsonPath('favorite', true);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 1])
            ->assertOk()
            ->assertJsonPath('quantity', 1);
    }

    public function test_unverified_accounts_are_stopped_before_critical_writes(): void
    {
        $buyer = User::factory()->unverified()->create(['role' => 'buyer']);
        $seller = User::factory()->unverified()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Unverified seller shop']);
        $admin = User::factory()->unverified()->create(['role' => 'admin']);
        $product = $this->createProduct(User::factory()->create(['role' => 'seller']));

        $this->actingAs($buyer)
            ->post(route('addresses.store'), $this->addressPayload())
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($buyer)
            ->postJson(route('addresses.store'), $this->addressPayload())
            ->assertForbidden();
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $buyer->id]);

        $this->actingAs($buyer)
            ->post(route('checkout.quick', $product), ['qty' => 1])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseMissing('orders', ['user_id' => $buyer->id]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseMissing('products', ['user_id' => $seller->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
        $this->actingAs($admin)->get(route('admin.profile'))->assertOk();
    }

    public function test_verified_local_and_google_users_keep_legitimate_access(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->post(route('addresses.store'), $this->addressPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_addresses', ['user_id' => $buyer->id]);

        $googleUser = User::factory()->create([
            'role' => 'buyer',
            'email' => 'verified-google-policy@example.com',
            'provider' => 'google',
            'provider_id' => 'verified-google-policy-subject',
            'password_set_at' => null,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => $googleUser->provider_id,
            'email' => $googleUser->email,
            'email_verified' => true,
        ]));

        $this->post(route('logout'))->assertRedirect('/');
        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($googleUser);

        $this->post(route('addresses.store'), $this->addressPayload('Google street'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $googleUser->id,
            'street' => 'Google street',
        ]);
    }

    public function test_email_change_immediately_revokes_critical_access_but_keeps_verification_available(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'profile_section' => 'email',
                'email' => 'changed-policy-email@example.com',
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->post(route('addresses.store'), $this->addressPayload())
            ->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk();
        $this->post(route('verification.send'))->assertRedirect();
    }

    public function test_route_policy_covers_critical_routes_without_gating_onboarding_or_cart(): void
    {
        $verifiedRoutes = [
            'profile.shop.update',
            'chats.start',
            'chats.product.start',
            'orders.chat.product',
            'chats.messages.store',
            'chats.support.dispute',
            'support.start',
            'orders.support',
            'checkout.quick',
            'checkout.prepare',
            'checkout.confirm',
            'checkout.create',
            'orders.confirmDelivery',
            'orders.requestCancellation',
            'orders.disputes.store',
            'addresses.store',
            'addresses.update',
            'addresses.destroy',
            'addresses.default',
            'review.store',
            'products.report',
            'seller.plans.request',
            'seller.products.store',
            'seller.products.update',
            'seller.products.destroy',
            'seller.products.gallery.delete',
            'seller.orders.chat.buyer',
            'seller.orders.updateStatus',
            'admin.dashboard',
        ];

        foreach ($verifiedRoutes as $routeName) {
            $this->assertContains('verified', $this->effectiveMiddleware($routeName), $routeName);
        }

        $unverifiedRoutes = [
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.update',
            'logout',
            'profile.update',
            'profile.destroy',
            'phone.send',
            'phone.verify',
            'shop.phone.send',
            'shop.phone.verify',
            'favorites.toggle',
            'cart.add',
            'seller.cabinet',
            'seller.products.index',
            'admin.profile',
            'admin.profile.update',
        ];

        foreach ($unverifiedRoutes as $routeName) {
            $this->assertNotContains('verified', $this->effectiveMiddleware($routeName), $routeName);
        }
    }

    private function effectiveMiddleware(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        return array_values(array_diff($route->gatherMiddleware(), $route->excludedMiddleware()));
    }

    private function createProduct(User $seller): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'title' => 'Verification policy product '.uniqid(),
            'sku' => 'VERIFY-'.uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Verification policy test product',
            'status' => 'active',
        ]);
    }

    private function addressPayload(string $street = 'Verification street'): array
    {
        return [
            'country' => 'Moldova',
            'city' => 'Chisinau',
            'street' => $street,
            'house' => '1',
        ];
    }
}
