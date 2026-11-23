<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Services\PhoneAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ShopPhonePublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $lock = tmpfile();
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        config(['backup.lock_path' => stream_get_meta_data($lock)['uri'],
            'cache.default' => 'array', 'cache.limiter' => 'array', 'session.driver' => 'array',
            'queue.default' => 'sync', 'mail.default' => 'array', 'logging.default' => 'null']);
    }

    private function fixture(): array
    {
        $owner = User::factory()->seller()->create(['phone' => '+37377111222', 'phone_verified_at' => now()]);
        $shop = $owner->shop()->create(['name' => 'Phone shop', 'phone' => '+37377333444']);
        return [$owner, $shop];
    }

    public function test_default_is_false_and_hidden_phone_never_appears_in_public_html_or_json(): void
    {
        [$owner, $shop] = $this->fixture();
        $this->assertFalse($shop->fresh()->phone_public);
        $this->get(route('seller.show', $shop->slug))->assertOk()
            ->assertDontSee($owner->phone)->assertDontSee($shop->phone);
        $json = $shop->load('user')->toJson();
        $this->assertStringNotContainsString($owner->phone, $json);
        $this->assertStringNotContainsString($shop->phone, $json);
        $this->assertNull($shop->public_phone);
    }

    public function test_owner_can_publish_only_current_verified_phone_and_turn_it_off(): void
    {
        [$owner, $shop] = $this->fixture();
        $this->actingAs($owner)->patch(route('profile.shop.update'), [
            'phone_public' => true, 'phone_public_for' => $owner->phone,
        ])->assertSessionHasNoErrors();
        $this->assertSame($owner->phone, $shop->public_phone);
        $this->get(route('seller.show', $shop->slug))->assertOk()
            ->assertSee('tel:'.$owner->phone, false)->assertDontSee($shop->phone);
        $this->assertSame($owner->phone, $shop->toArray()['public_phone']);
        $this->patch(route('profile.shop.update'), ['phone_public' => false])->assertSessionHasNoErrors();
        $this->assertNull($shop->public_phone);
    }

    public function test_empty_unverified_and_deleted_owners_fail_closed_even_with_stale_models(): void
    {
        [$owner, $shop] = $this->fixture();
        $shop->forceFill(['phone_public' => true])->save();
        $shop->load('user');
        foreach ([['phone_verified_at' => null], ['phone' => null, 'phone_verified_at' => now()],
            ['phone' => '+37377111222', 'phone_verified_at' => now(), 'deleted_at' => now()]] as $state) {
            DB::table('users')->where('id', $owner->id)->update($state);
            $this->assertNull($shop->public_phone);
            $this->assertFalse($shop->is_phone_verified);
            $this->assertStringNotContainsString('+37377111222', $shop->toJson());
        }
    }

    public function test_publication_rejects_unverified_and_stale_form_numbers(): void
    {
        [$owner, $shop] = $this->fixture();
        $this->actingAs($owner)->patch(route('profile.shop.update'), [
            'phone_public' => true, 'phone_public_for' => '+37377999888',
        ])->assertSessionHasErrors('phone_public');
        $owner->forceFill(['phone_verified_at' => null])->save();
        $this->patch(route('profile.shop.update'), [
            'phone_public' => true, 'phone_public_for' => $owner->phone,
        ])->assertSessionHasErrors('phone_public');
        $this->assertFalse($shop->fresh()->phone_public);
    }

    public function test_old_form_cannot_publish_a_reverified_replacement_number(): void
    {
        [$owner, $shop] = $this->fixture();
        $oldPhone = $owner->phone;
        $shop->forceFill(['phone_public' => true])->save();
        app(PhoneAssignmentService::class)->assignToUser($owner, '+37377555666');
        $owner = $owner->fresh();
        $owner->forceFill(['phone_verified_at' => now()])->save();
        $this->actingAs($owner)->patch(route('profile.shop.update'), [
            'phone_public' => true, 'phone_public_for' => $oldPhone,
        ])->assertSessionHasErrors('phone_public');
        $this->assertNull($shop->public_phone);
        $this->get(route('seller.show', $shop->slug))->assertOk()->assertDontSee($owner->phone);
    }

    public function test_invalid_stored_phone_cannot_be_emitted_as_a_public_link(): void
    {
        [$owner, $shop] = $this->fixture();
        $shop->forceFill(['phone_public' => true])->save();
        DB::table('users')->where('id', $owner->id)->update(['phone' => 'javascript:alert(1)']);
        $this->assertNull($shop->public_phone);
        $this->assertStringNotContainsString('javascript:alert(1)', $shop->load('user')->toJson());
        $this->get(route('seller.show', $shop->slug))->assertOk()->assertDontSee('javascript:alert(1)', false);
    }

    public function test_change_resets_all_owned_shops_and_preserves_legacy_data(): void
    {
        [$owner, $shop] = $this->fixture();
        $other = $owner->shop()->create(['name' => 'Second shop']);
        $unrelated = User::factory()->seller()->create()->shop()->create(['name' => 'Unrelated']);
        foreach ([$shop, $other, $unrelated] as $s) $s->forceFill(['phone_public' => true])->save();
        $legacy = $shop->getRawOriginal('phone');
        $verifiedAt = $owner->phone_verified_at;
        $phones = app(PhoneAssignmentService::class);
        $this->assertFalse($phones->assignToUser($owner, '+373 77 111 222'));
        $this->assertTrue($shop->fresh()->phone_public);
        $this->assertTrue($verifiedAt->equalTo($owner->fresh()->phone_verified_at));
        $phones->assignToUser($owner, '+37377555666');
        $this->assertFalse($shop->fresh()->phone_public);
        $this->assertFalse($other->fresh()->phone_public);
        $this->assertTrue($unrelated->fresh()->phone_public);
        $this->assertNull($owner->fresh()->phone_verified_at);
        $this->assertSame($legacy, $shop->fresh()->phone);
        $owner->fresh()->forceFill(['phone_verified_at' => now()])->save();
        $this->assertNull($shop->public_phone);
    }

    public function test_visibility_and_phone_change_roll_back_together(): void
    {
        [$owner, $shop] = $this->fixture();
        $shop->forceFill(['phone_public' => true])->save();
        $before = $owner->fresh()->only(['phone', 'phone_verified_at']);
        DB::beginTransaction();
        try {
            app(PhoneAssignmentService::class)->assignToUser($owner, '+37377555666');
            $this->assertFalse($shop->fresh()->phone_public);
        } finally {
            DB::rollBack();
        }
        $this->assertEquals($before, $owner->fresh()->only(['phone', 'phone_verified_at']));
        $this->assertTrue($shop->fresh()->phone_public);
    }

    public function test_shop_badge_uses_personal_verification_and_cabinet_has_no_legacy_edit_form(): void
    {
        [$owner, $shop] = $this->fixture();
        $this->assertNull($shop->phone_verified_at);
        $this->assertTrue($shop->is_phone_verified);
        $this->actingAs($owner)->get(route('profile.edit'))->assertOk()
            ->assertSee($owner->phone)->assertDontSee($shop->phone)
            ->assertSee('name="phone_public"', false)->assertDontSee('id="shop-phone-input"', false);
        $owner->forceFill(['phone_verified_at' => null])->save();
        $shop->forceFill(['phone_verified_at' => now()])->save();
        $this->assertFalse($shop->is_phone_verified);
    }

    public function test_other_owner_cannot_change_target_shop_visibility(): void
    {
        [$owner, $shop] = $this->fixture();
        $attacker = User::factory()->seller()->create(['phone' => '+37377999888', 'phone_verified_at' => now()]);
        $ownShop = $attacker->shop()->create(['name' => 'Own shop']);
        $targetBefore = $shop->fresh()->getRawOriginal();
        // All supplied phone/verification data is valid for the authenticated owner.
        // A forged target identifier must not select someone else's shop.
        $this->actingAs($attacker)->patch(route('profile.shop.update'), [
            'shop_id' => $shop->id, 'user_id' => $owner->id,
            'phone_public' => true, 'phone_public_for' => $attacker->phone,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($ownShop->fresh()->phone_public);
        $this->assertSame($targetBefore, $shop->fresh()->getRawOriginal());

        $shop->forceFill(['phone_public' => true])->save();
        $this->patch(route('profile.shop.update'), ['shop_id' => $shop->id, 'phone_public' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($ownShop->fresh()->phone_public);
        $this->assertTrue($shop->fresh()->phone_public);
    }

    public function test_cached_pre_transition_models_cannot_leak_revoked_phone_in_json_or_api(): void
    {
        [$owner, $shop] = $this->fixture();
        $shop->forceFill(['phone_public' => true])->save();
        $shop->load('user');
        // Simulate the visibility metadata in a model cached by the old release.
        $shop->setHidden([])->setAppends([]);
        $shop->user->setHidden(['password', 'remember_token', 'phone_verification_code']);
        Cache::put('test.phone.cached-shop', serialize($shop), 60);
        $this->actingAs($owner)->patch(route('profile.shop.update'), ['phone_public' => false])
            ->assertSessionHasNoErrors();
        $cached = unserialize(Cache::get('test.phone.cached-shop'));
        $this->assertNull($cached->public_phone);
        $json = $cached->toJson();
        $this->assertStringNotContainsString($owner->phone, $json);
        $this->assertStringNotContainsString($shop->phone, $json);
        $this->assertArrayNotHasKey('phone', $cached->toArray());
        $this->assertArrayNotHasKey('phone', $cached->user->toArray());
        $this->getJson(route('search.suggest', ['q' => 'Phone shop']))->assertOk()
            ->assertDontSee($owner->phone)->assertDontSee($shop->phone);
        $response = $this->get(route('seller.show', $shop->slug))->assertOk()
            ->assertDontSee($owner->phone)->assertDontSee($shop->phone);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        Cache::forget('test.phone.cached-shop');
    }

    public function test_product_pages_ajax_and_nested_model_json_respect_hidden_phone(): void
    {
        [$owner, $shop] = $this->fixture();
        $category = Category::factory()->create();
        $product = Product::create([
            'user_id' => $owner->id, 'category_id' => $category->id,
            'title' => 'Phone privacy product', 'price' => 100, 'stock' => 5, 'status' => 'active',
        ]);
        $shop->forceFill(['phone_public' => true])->save();
        // Warm the actual product-page model cache before revoking permission.
        $this->get(route('product.show', $product->slug))->assertOk();
        $this->actingAs($owner)->patch(route('profile.shop.update'), ['phone_public' => false])
            ->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        foreach ([route('product.show', $product->slug), route('category.ajax', $category->slug)] as $url) {
            $this->get($url)->assertOk()->assertDontSee($owner->phone)->assertDontSee($shop->phone);
        }
        $json = $product->load('seller.shop')->toJson();
        $this->assertStringNotContainsString($owner->phone, $json);
        $this->assertStringNotContainsString($shop->phone, $json);
    }

    public function test_whatsapp_link_is_unchanged_by_phone_visibility(): void
    {
        [$owner, $shop] = $this->fixture();
        $link = 'https://wa.me/37377999000';
        $shop->update(['whatsapp' => $link]);
        foreach ([true, false] as $public) {
            $this->actingAs($owner)->patch(route('profile.shop.update'), [
                'phone_public' => $public, 'phone_public_for' => $owner->phone,
            ])->assertSessionHasNoErrors();
            $this->assertSame($link, $shop->fresh()->whatsapp);
            $this->get(route('seller.show', $shop->slug))->assertOk()->assertSee($link, false);
        }
    }
}
