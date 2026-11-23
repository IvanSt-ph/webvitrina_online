<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopPhoneProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep real middleware/queue locks on a test-owned inode until rollback finishes.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array',
            'logging.default' => 'null',
        ]);
    }

    public function test_legacy_shop_phone_requests_are_rejected_without_changing_history(): void
    {
        $seller = User::factory()->seller()->create(['phone' => '+37377111999', 'phone_verified_at' => now()]);
        $shop = $seller->shop()->create([
            'name' => 'Seller shop', 'phone' => '+37377111222', 'phone_verified_at' => now(),
            'phone_verification_code' => '123456', 'phone_verification_expires_at' => now()->addMinutes(10),
        ]);
        $before = $shop->fresh()->getRawOriginal();
        $userBefore = $seller->fresh()->only(['phone', 'phone_verified_at']);
        foreach (['+37377333444', '+37377111222', '', null] as $phone) {
            $this->actingAs($seller)->patch(route('profile.shop.update'), ['phone' => $phone])
                ->assertSessionHasErrors('phone');
            $this->assertSame($before, $shop->fresh()->getRawOriginal());
            $this->assertEquals($userBefore, $seller->fresh()->only(['phone', 'phone_verified_at']));
        }
    }

    public function test_shop_phone_cannot_duplicate_another_shops_phone_without_current_password(): void
    {
        $owner = User::factory()->seller()->create();
        $owner->shop()->create(['name' => 'Owner shop', 'phone' => '+37377111222']);
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create(['name' => 'Seller shop', 'phone' => null]);

        $this->actingAs($seller)
            ->patch(route('profile.shop.update'), ['phone' => '+373 77 111 222'])
            ->assertSessionHasErrors('phone');

        $this->assertNull($shop->fresh()->phone);
    }

    public function test_legacy_aliases_cannot_add_a_phone_or_create_a_shop(): void
    {
        $seller = User::factory()->seller()->create(['phone' => null]);
        foreach ([['phone' => '+37377111222'], ['phone_full' => '+37377111222'], ['update_type' => 'phone']] as $payload) {
            $this->actingAs($seller)->patchJson(route('profile.shop.update'), $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('phone');
            $this->assertNull($seller->fresh()->phone);
            $this->assertFalse($seller->shop()->exists());
        }
    }

    public function test_shop_phone_cannot_duplicate_another_users_phone_without_current_password(): void
    {
        User::factory()->create(['phone' => '+37377111222']);
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create(['name' => 'Seller shop', 'phone' => null]);

        $this->actingAs($seller)
            ->patch(route('profile.shop.update'), ['phone' => '+373 77 111 222'])
            ->assertSessionHasErrors('phone');

        $this->assertNull($shop->fresh()->phone);
    }

    public function test_user_email_change_still_requires_the_correct_current_password(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->patch(route('profile.update'), [
                'profile_section' => 'email',
                'email' => 'new-seller@example.test',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($seller->email, $seller->fresh()->email);

        $this->actingAs($seller)
            ->patch(route('profile.update'), [
                'profile_section' => 'email',
                'email' => 'new-seller@example.test',
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors();

        $seller->refresh();
        $this->assertSame('new-seller@example.test', $seller->email);
        $this->assertNull($seller->email_verified_at);
    }

    public function test_user_phone_change_does_not_require_current_password(): void
    {
        $seller = User::factory()->seller()->create(['phone' => null]);

        $this->actingAs($seller)
            ->patch(route('profile.update'), [
                'profile_section' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('+37377111222', $seller->fresh()->phone);
    }
}
