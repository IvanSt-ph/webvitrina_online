<?php

namespace Tests\Feature;

use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\ShopPhoneVerificationController;
use App\Models\Shop;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PhoneVerificationRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_user_verification_cannot_verify_a_new_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+37377111222',
            'phone_verified_at' => null,
            'phone_verification_code' => '111111',
        ]);
        $seenPhone = null;

        $this->bindUserVerifier(function (array $payload) use ($user, &$seenPhone): object {
            $seenPhone = $payload['to'];
            User::whereKey($user->id)->update([
                'phone' => '+37377333444',
                'phone_verified_at' => null,
                'phone_verification_code' => '222222',
            ]);

            return (object) ['status' => 'approved'];
        });

        $this->actingAs($user)
            ->withSession(['phone_verification_sent' => true])
            ->post(route('phone.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertSame('+37377111222', $seenPhone);
        $this->assertSame('+37377333444', $user->phone);
        $this->assertNull($user->phone_verified_at);
        $this->assertSame('222222', $user->phone_verification_code);

        Auth::logout();

        $this->post('/login', [
            'login' => '+373 77 333 444',
            'password' => 'password',
        ])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_old_shop_verification_cannot_verify_a_new_phone(): void
    {
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create([
            'name' => 'Seller shop',
            'phone' => '+37377111222',
            'phone_verified_at' => null,
            'phone_verification_code' => 'old-code',
            'phone_verification_expires_at' => now()->addMinutes(5),
        ]);
        $newExpiry = now()->addMinutes(10);
        $seenPhone = null;

        $this->bindShopVerifier(function (array $payload) use ($shop, $newExpiry, &$seenPhone): object {
            $seenPhone = $payload['to'];
            Shop::whereKey($shop->id)->update([
                'phone' => '+37377333444',
                'phone_verified_at' => null,
                'phone_verification_code' => 'new-code',
                'phone_verification_expires_at' => $newExpiry,
            ]);

            return (object) ['status' => 'approved'];
        });

        $this->actingAs($seller)
            ->withSession(['shop_phone_verification_sent' => true])
            ->post(route('shop.phone.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $shop->refresh();
        $this->assertSame('+37377111222', $seenPhone);
        $this->assertSame('+37377333444', $shop->phone);
        $this->assertNull($shop->phone_verified_at);
        $this->assertSame('new-code', $shop->phone_verification_code);
        $this->assertSame(
            $newExpiry->format('Y-m-d H:i:s'),
            $shop->phone_verification_expires_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_clearing_shop_phone_clears_all_verification_state(): void
    {
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create([
            'name' => 'Seller shop',
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
            'phone_verification_code' => '123456',
            'phone_verification_expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($seller)
            ->patch(route('profile.shop.update'), ['phone' => ''])
            ->assertSessionHasNoErrors();

        $shop->refresh();
        $this->assertNull($shop->phone);
        $this->assertNull($shop->phone_verified_at);
        $this->assertNull($shop->phone_verification_code);
        $this->assertNull($shop->phone_verification_expires_at);
    }

    public function test_current_user_phone_can_still_be_verified(): void
    {
        $user = User::factory()->create([
            'phone' => '+37377111222',
            'phone_verified_at' => null,
            'phone_verification_code' => '111111',
        ]);

        $this->bindUserVerifier(fn (): object => (object) ['status' => 'approved']);

        $this->actingAs($user)
            ->post(route('phone.verify'), ['code' => '123456'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->phone_verification_code);

        Auth::logout();

        $this->post('/login', [
            'login' => '+373 77 111 222',
            'password' => 'password',
        ])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_current_shop_phone_can_still_be_verified(): void
    {
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create([
            'name' => 'Seller shop',
            'phone' => '+37377111222',
            'phone_verified_at' => null,
            'phone_verification_code' => '111111',
            'phone_verification_expires_at' => now()->addMinutes(10),
        ]);

        $this->bindShopVerifier(fn (): object => (object) ['status' => 'approved']);

        $this->actingAs($seller)
            ->post(route('shop.phone.verify'), ['code' => '123456'])
            ->assertSessionHasNoErrors();

        $shop->refresh();
        $this->assertNotNull($shop->phone_verified_at);
        $this->assertNull($shop->phone_verification_code);
        $this->assertNull($shop->phone_verification_expires_at);
    }

    private function bindUserVerifier(Closure $callback): void
    {
        $this->configureTwilio();
        $client = $this->fakeTwilioClient($callback);

        $this->app->instance(PhoneVerificationController::class, new class($client) extends PhoneVerificationController {
            public function __construct(private readonly object $client)
            {
            }

            protected function twilio(): object
            {
                return $this->client;
            }
        });
    }

    private function bindShopVerifier(Closure $callback): void
    {
        $this->configureTwilio();
        $client = $this->fakeTwilioClient($callback);

        $this->app->instance(ShopPhoneVerificationController::class, new class($client) extends ShopPhoneVerificationController {
            public function __construct(private readonly object $client)
            {
            }

            protected function twilio(): object
            {
                return $this->client;
            }
        });
    }

    private function configureTwilio(): void
    {
        config()->set('services.twilio.sid', 'test-sid');
        config()->set('services.twilio.token', 'test-token');
        config()->set('services.twilio.verify_sid', 'test-verify-sid');
    }

    private function fakeTwilioClient(Closure $callback): object
    {
        $checks = new class($callback) {
            public function __construct(private readonly Closure $callback)
            {
            }

            public function create(array $payload): object
            {
                return ($this->callback)($payload);
            }
        };

        $service = new class($checks) {
            public function __construct(public readonly object $verificationChecks)
            {
            }
        };

        $v2 = new class($service) {
            public function __construct(private readonly object $service)
            {
            }

            public function services(string $sid): object
            {
                return $this->service;
            }
        };

        return (object) [
            'verify' => (object) ['v2' => $v2],
        ];
    }
}
