<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\PhoneAssignmentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhoneUniquenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_has_separate_unique_phone_indexes(): void
    {
        $schema = DB::connection()->getDatabaseName();

        foreach (['users' => 'users_phone_unique', 'shops' => 'shops_phone_unique'] as $table => $index) {
            $this->assertTrue(DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $schema)
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $index)
                ->where('NON_UNIQUE', 0)
                ->where('COLUMN_NAME', 'phone')
                ->exists());
        }
    }

    public function test_database_rejects_duplicate_user_phones(): void
    {
        User::factory()->create(['phone' => '+37377111001']);

        $this->expectException(UniqueConstraintViolationException::class);
        User::factory()->create(['phone' => '+37377111001']);
    }

    public function test_database_rejects_duplicate_shop_phones(): void
    {
        $first = User::factory()->seller()->create();
        $second = User::factory()->seller()->create();
        $first->shop()->create(['name' => 'First shop', 'phone' => '+37377111002']);

        $this->expectException(UniqueConstraintViolationException::class);
        $second->shop()->create(['name' => 'Second shop', 'phone' => '+37377111002']);
    }

    public function test_user_phone_conflicting_with_another_owners_shop_is_rejected(): void
    {
        $owner = User::factory()->seller()->create();
        $owner->shop()->create(['name' => 'Owner shop', 'phone' => '+37377111003']);
        $buyer = User::factory()->create(['phone' => null]);

        $this->expectPhoneConflict(fn () => app(PhoneAssignmentService::class)
            ->assignToUser($buyer, '+373 77 111 003'));
        $this->assertNull($buyer->fresh()->phone);
    }

    public function test_shop_phone_conflicting_with_another_user_is_rejected(): void
    {
        User::factory()->create(['phone' => '+37377111004']);
        $seller = User::factory()->seller()->create();
        $shop = $seller->shop()->create(['name' => 'Seller shop', 'phone' => null]);

        $this->expectPhoneConflict(fn () => app(PhoneAssignmentService::class)
            ->assignToShop($shop, '+373 77 111 004'));
        $this->assertNull($shop->fresh()->phone);
    }

    public function test_user_and_own_shop_can_share_one_phone(): void
    {
        $seller = User::factory()->seller()->create(['phone' => null]);
        $shop = $seller->shop()->create(['name' => 'Seller shop', 'phone' => null]);
        $phones = app(PhoneAssignmentService::class);

        $phones->assignToUser($seller, '+373 77 111 005');
        $phones->assignToShop($shop, '+373 77 111 005');

        $this->assertSame('+37377111005', $seller->fresh()->phone);
        $this->assertSame('+37377111005', $shop->fresh()->phone);
    }

    public function test_many_null_user_and_shop_phones_are_allowed(): void
    {
        $first = User::factory()->seller()->create(['phone' => null]);
        $second = User::factory()->seller()->create(['phone' => null]);
        $first->shop()->create(['name' => 'First shop', 'phone' => null]);
        $second->shop()->create(['name' => 'Second shop', 'phone' => null]);

        $this->assertSame(2, User::whereNull('phone')->count());
        $this->assertSame(2, Shop::whereNull('phone')->count());
    }

    public function test_duplicate_key_is_converted_to_phone_validation_error(): void
    {
        $phone = '+37377111006';
        User::factory()->create(['phone' => $phone]);

        $this->expectPhoneConflict(function () use ($phone): void {
            app(PhoneAssignmentService::class)->run($phone, function () use ($phone): void {
                User::factory()->create(['phone' => $phone]);
            });
        });
    }

    public function test_seller_registration_keeps_same_owner_user_shop_phone(): void
    {
        $this->post('/register', [
            'name' => 'Phone Seller',
            'email' => 'phone-seller@example.test',
            'phone' => '+373 77 111 007',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'seller',
            'terms' => '1',
        ])->assertSessionHasNoErrors();

        $seller = User::where('email', 'phone-seller@example.test')->firstOrFail();
        $this->assertSame('+37377111007', $seller->phone);
        $this->assertSame($seller->phone, $seller->shop->phone);
    }

    public function test_registration_rejects_phone_owned_by_another_shop(): void
    {
        $owner = User::factory()->seller()->create();
        $owner->shop()->create(['name' => 'Owner shop', 'phone' => '+37377111008']);

        $this->post('/register', [
            'name' => 'Conflicting Buyer',
            'email' => 'conflicting-buyer@example.test',
            'phone' => '+373 77 111 008',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'buyer',
            'terms' => '1',
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'conflicting-buyer@example.test']);
    }

    public function test_admin_update_rejects_phone_owned_by_another_users_shop(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->seller()->create();
        $owner->shop()->create(['name' => 'Owner shop', 'phone' => '+37377111009']);
        $buyer = User::factory()->create(['phone' => null]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $buyer), [
                'name' => $buyer->name,
                'email' => $buyer->email,
                'phone' => '+373 77 111 009',
                'role' => $buyer->role,
            ])
            ->assertSessionHasErrors('phone');

        $this->assertNull($buyer->fresh()->phone);
    }

    public function test_admin_can_create_seller_with_same_phone_on_own_user_and_shop(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Created Seller',
                'email' => 'admin-created-seller@example.test',
                'phone' => '+373 77 111 010',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'seller',
            ])
            ->assertSessionHasNoErrors();

        $seller = User::where('email', 'admin-created-seller@example.test')->firstOrFail();
        $this->assertSame('+37377111010', $seller->phone);
        $this->assertSame($seller->phone, $seller->shop->phone);
    }

    private function expectPhoneConflict(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected a controlled phone validation conflict.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('phone', $exception->errors());
        }
    }
}
