<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerPhoneProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_add_account_phone_without_current_password(): void
    {
        $buyer = User::factory()->create(['phone' => null]);

        $this->actingAs($buyer)
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasNoErrors();

        $buyer->refresh();
        $this->assertSame('+37377111222', $buyer->phone);
        $this->assertNull($buyer->phone_verified_at);
    }

    public function test_buyer_can_change_verified_account_phone_without_current_password_and_verification_is_reset(): void
    {
        $buyer = User::factory()->create([
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
            'phone_verification_code' => '123456',
        ]);

        $this->actingAs($buyer)
            ->withSession(['phone_verification_sent' => true])
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'phone',
                'phone' => '+373 77 333 444',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('phone_verification_sent');

        $buyer->refresh();
        $this->assertSame('+37377333444', $buyer->phone);
        $this->assertNull($buyer->phone_verified_at);
        $this->assertNull($buyer->phone_verification_code);
    }

    public function test_unchanged_account_phone_keeps_verification_state(): void
    {
        $buyer = User::factory()->create([
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
            'phone_verification_code' => '123456',
        ]);
        $verifiedAt = $buyer->phone_verified_at;

        $this->actingAs($buyer)
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasNoErrors();

        $buyer->refresh();
        $this->assertTrue($verifiedAt->equalTo($buyer->phone_verified_at));
        $this->assertSame('123456', $buyer->phone_verification_code);
    }

    public function test_contacts_form_phone_only_change_does_not_require_current_password(): void
    {
        $buyer = User::factory()->create(['phone' => null]);

        $this->actingAs($buyer)
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'contacts',
                'email' => $buyer->email,
                'phone' => '+373 77 111 222',
                'phone_dirty' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('+37377111222', $buyer->fresh()->phone);
    }

    public function test_contacts_form_email_change_still_requires_current_password(): void
    {
        $buyer = User::factory()->create(['phone' => null]);

        $this->actingAs($buyer)
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'contacts',
                'email' => 'changed-buyer@example.test',
                'phone' => '+373 77 111 222',
                'phone_dirty' => true,
            ])
            ->assertSessionHasErrors('current_password');

        $buyer->refresh();
        $this->assertNotSame('changed-buyer@example.test', $buyer->email);
        $this->assertNull($buyer->phone);
    }

    public function test_account_phone_cannot_duplicate_another_users_phone(): void
    {
        User::factory()->create(['phone' => '+37377111222']);
        $buyer = User::factory()->create(['phone' => null]);

        $this->actingAs($buyer)
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertNull($buyer->fresh()->phone);
    }

    public function test_account_phone_cannot_duplicate_another_users_shop_phone(): void
    {
        $seller = User::factory()->seller()->create();
        $seller->shop()->create(['name' => 'Phone owner shop', 'phone' => '+37377111222']);
        $buyer = User::factory()->create(['phone' => null]);

        $this->actingAs($buyer)
            ->patch(route('buyer.profile.update'), [
                'profile_section' => 'phone',
                'phone' => '+373 77 111 222',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertNull($buyer->fresh()->phone);
    }

    public function test_admin_phone_change_resets_account_phone_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create([
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
            'phone_verification_code' => '123456',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $buyer), $this->adminPayload($buyer, '+373 77 333 444'))
            ->assertSessionHasNoErrors();

        $buyer->refresh();
        $this->assertSame('+37377333444', $buyer->phone);
        $this->assertNull($buyer->phone_verified_at);
        $this->assertNull($buyer->phone_verification_code);
    }

    public function test_admin_update_with_unchanged_phone_keeps_account_phone_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->create([
            'phone' => '+37377111222',
            'phone_verified_at' => now(),
            'phone_verification_code' => '123456',
        ]);
        $verifiedAt = $buyer->phone_verified_at;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $buyer), $this->adminPayload($buyer, '+373 77 111 222'))
            ->assertSessionHasNoErrors();

        $buyer->refresh();
        $this->assertTrue($verifiedAt->equalTo($buyer->phone_verified_at));
        $this->assertSame('123456', $buyer->phone_verification_code);
    }

    private function adminPayload(User $user, string $phone): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $phone,
            'role' => $user->role,
        ];
    }
}
