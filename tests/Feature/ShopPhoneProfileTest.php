<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopPhoneProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_add_shop_phone_without_current_password(): void
    {
        $seller = User::factory()->seller()->create(['phone' => null]);
        $shop = $seller->shop()->create(['name' => 'Seller shop']);

        $this->actingAs($seller)
            ->patch(route('profile.shop.update'), [
                'update_type' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $shop->refresh();
        $this->assertSame('+37377111222', $shop->phone);
        $this->assertNull($shop->phone_verified_at);
        $this->assertNull($seller->fresh()->phone);
    }

    public function test_seller_can_change_verified_shop_phone_without_current_password_and_verification_is_reset(): void
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
            ->patch(route('profile.shop.update'), [
                'update_type' => 'phone',
                'phone' => '+373 77 333 444',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $shop->refresh();
        $this->assertSame('+37377333444', $shop->phone);
        $this->assertNull($shop->phone_verified_at);
        $this->assertNull($shop->phone_verification_code);
        $this->assertNull($shop->phone_verification_expires_at);
        $this->assertFalse($shop->is_phone_verified);
        $this->assertTrue($shop->needsPhoneVerification());
    }

    public function test_unchanged_shop_phone_keeps_verification_state_without_current_password(): void
    {
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create([
            'name' => 'Seller shop',
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
            'phone_verification_code' => '123456',
            'phone_verification_expires_at' => now()->addMinutes(10),
        ]);
        $verifiedAt = $shop->phone_verified_at;
        $codeExpiresAt = $shop->phone_verification_expires_at;

        $this->actingAs($seller)
            ->patch(route('profile.shop.update'), [
                'update_type' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $shop->refresh();
        $this->assertSame('+37377111222', $shop->phone);
        $this->assertTrue($shop->is_phone_verified);
        $this->assertTrue($verifiedAt->equalTo($shop->phone_verified_at));
        $this->assertSame('123456', $shop->phone_verification_code);
        $this->assertTrue($codeExpiresAt->equalTo($shop->phone_verification_expires_at));
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
