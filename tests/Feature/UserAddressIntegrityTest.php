<?php

namespace Tests\Feature;

use App\Http\Controllers\UserAddressController;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserAddressIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_replace(['country' => 'Moldova', 'city' => 'Chisinau', 'street' => 'Original street'], $extra);
    }

    private function address(User $user, bool $default = false): UserAddress
    {
        return $user->addresses()->create($this->payload(['is_default' => $default]));
    }

    private function write(string $action, UserAddress $address): TestResponse
    {
        return match ($action) {
            'store' => $this->post(route('addresses.store'), $this->payload(['is_default' => '1'])),
            'update' => $this->put(route('addresses.update', $address), $this->payload(['street' => 'Changed street', 'is_default' => '1'])),
            'destroy' => $this->delete(route('addresses.destroy', $address)),
            'default' => $this->post(route('addresses.default', $address)),
        };
    }

    public function test_crud_preserves_optional_default_semantics_and_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $this->address($other, true);
        $this->actingAs($user)->post(route('addresses.store'), $this->payload(['user_id' => $other->id]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $first = $user->addresses()->sole();
        $this->assertFalse((bool) $first->is_default);
        $this->post(route('addresses.store'), $this->payload(['is_default' => true]))->assertRedirect()->assertSessionHasNoErrors();
        $second = $user->addresses()->whereKeyNot($first->id)->sole();
        $this->assertTrue((bool) $second->is_default);
        $this->put(route('addresses.update', $second), $this->payload(['street' => 'Changed']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue((bool) $second->fresh()->is_default);
        $this->assertSame('Changed', $second->fresh()->street);
        $this->post(route('addresses.default', $first))->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('addresses.default', $first))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([$first->id], $user->addresses()->where('is_default', true)->pluck('id')->all());
        $this->delete(route('addresses.destroy', $second))->assertRedirect();
        $this->assertTrue((bool) $first->fresh()->is_default);
        $replacement = $this->address($user);
        $this->delete(route('addresses.destroy', $first))->assertRedirect();
        $this->assertSame(0, $user->addresses()->where('is_default', true)->count());
        $this->assertNotNull($replacement->fresh());
        $this->assertTrue((bool) $foreign->fresh()->is_default);
    }

    public static function booleanValues(): array
    {
        return ['true' => [true], 'one' => [1], 'string one' => ['1'],
            'false' => [false], 'zero' => [0], 'string zero' => ['0']];
    }

    #[DataProvider('booleanValues')]
    public function test_boolean_values_for_creation_and_edit_of_existing_default(mixed $value): void
    {
        $user = User::factory()->create();
        $old = $this->address($user, true);
        $this->actingAs($user)->post(route('addresses.store'), $this->payload(['is_default' => $value]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $new = $user->addresses()->whereKeyNot($old->id)->sole();
        $this->assertSame((bool) $value, (bool) $new->is_default);
        $this->assertSame(! (bool) $value, (bool) $old->fresh()->is_default);
        $target = (bool) $value ? $new : $old;
        // Includes an unchanged HTML checkbox ('1') and otherwise unchanged data.
        $this->put(route('addresses.update', $target), $this->payload(['is_default' => $value]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame((bool) $value, (bool) $target->fresh()->is_default);
        $this->assertSame((int) (bool) $value, $user->addresses()->where('is_default', true)->count());
    }

    public static function existingActions(): array
    {
        return [['update'], ['destroy'], ['default']];
    }

    #[DataProvider('existingActions')]
    public function test_foreign_address_is_rejected_for_every_role(string $action): void
    {
        $owner = User::factory()->create();
        $foreign = $this->address($owner, true);
        foreach (['buyer', 'seller', 'admin'] as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $own = $this->address($actor, true);
            $this->actingAs($actor);
            $this->write($action, $foreign)->assertForbidden();
            $this->assertTrue((bool) $own->fresh()->is_default);
            $this->assertTrue((bool) $foreign->fresh()->is_default);
            $this->assertSame('Original street', $foreign->fresh()->street);
        }
    }

    public function test_auth_verification_and_invalid_data_leave_the_address_book_unchanged(): void
    {
        $user = User::factory()->unverified()->create();
        $address = $this->address($user, true);
        foreach (['store', 'update', 'destroy', 'default'] as $action) {
            $this->write($action, $address)->assertRedirect(route('login'));
        }
        $this->actingAs($user);
        foreach (['store', 'update', 'destroy', 'default'] as $action) {
            $this->write($action, $address)->assertRedirect(route('verification.notice'));
        }
        $user->markEmailAsVerified();
        $this->putJson(route('addresses.update', $address), $this->payload(['country' => '', 'is_default' => '1']))
            ->assertStatus(422)->assertJsonValidationErrors('country');
        $this->postJson(route('addresses.store'), $this->payload(['is_default' => 'invalid']))->assertStatus(422);
        $this->assertSame(1, $user->addresses()->count());
        $this->assertTrue((bool) $address->fresh()->is_default);
    }

    #[DataProvider('existingActions')]
    public function test_target_deleted_after_binding_does_not_clear_another_default(string $action): void
    {
        $user = User::factory()->create();
        $default = $this->address($user, true);
        $target = $this->address($user);
        Route::bind('address', function ($id) {
            $bound = UserAddress::findOrFail($id);
            $bound->delete(); // Simulate a committed deletion before the controller transaction.

            return $bound;
        });
        $this->actingAs($user);
        $this->write($action, $target)->assertNotFound();
        $this->assertNull($target->fresh());
        $this->assertTrue((bool) $default->fresh()->is_default);
    }

    #[DataProvider('existingActions')]
    public function test_ownership_is_rechecked_after_binding(string $action): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $default = $this->address($user, true);
        $target = $this->address($user);
        Route::bind('address', function ($id) use ($other) {
            $bound = UserAddress::findOrFail($id);
            UserAddress::whereKey($id)->update(['user_id' => $other->id]);

            return $bound;
        });
        $this->actingAs($user);
        $this->write($action, $target)->assertForbidden();
        $this->assertSame($other->id, $target->fresh()->user_id);
        $this->assertTrue((bool) $default->fresh()->is_default);
    }

    public function test_update_without_default_uses_current_state_not_route_snapshot(): void
    {
        $user = User::factory()->create();
        $target = $this->address($user, true);
        $other = $this->address($user);
        Route::bind('address', function ($id) use ($other) {
            $bound = UserAddress::findOrFail($id);
            app(UserAddressController::class)->makeDefault($other);

            return $bound;
        });
        $this->actingAs($user)->put(route('addresses.update', $target), $this->payload(['street' => 'Changed']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse((bool) $target->fresh()->is_default);
        $this->assertTrue((bool) $other->fresh()->is_default);
        $this->assertSame('Changed', $target->fresh()->street);
    }

    public static function failedWrites(): array
    {
        $cases = [];
        foreach (['store', 'update', 'destroy', 'default'] as $action) {
            foreach (['cancel', 'throw-after-write'] as $failure) {
                $cases[$action.' '.$failure] = [$action, $failure];
            }
        }

        return $cases;
    }

    #[DataProvider('failedWrites')]
    public function test_failed_write_rolls_back_the_whole_address_book(string $action, string $failure): void
    {
        $user = User::factory()->create();
        $this->address($user, true);
        $target = $this->address($user);
        $before = DB::table('user_addresses')->orderBy('id')->get()->toJson();
        $level = DB::transactionLevel();
        $event = $failure === 'cancel' ? ($action === 'destroy' ? 'deleting' : 'saving')
            : match ($action) { 'store' => 'created', 'destroy' => 'deleted', default => 'updated' };
        UserAddress::{$event}(function () use ($failure) {
            if ($failure === 'cancel') {
                return false;
            }
            throw new \RuntimeException('Injected address write failure');
        });
        $this->actingAs($user)->withoutExceptionHandling();
        try {
            $this->write($action, $target);
            $this->fail('Expected failed address write');
        } catch (\RuntimeException $exception) {
            $this->assertSame($failure === 'cancel'
                ? ($action === 'destroy' ? 'Address deletion was not completed.' : 'Address save was not completed.')
                : 'Injected address write failure', $exception->getMessage());
        }
        $this->assertSame($before, DB::table('user_addresses')->orderBy('id')->get()->toJson());
        $this->assertSame($level, DB::transactionLevel());
    }
}
