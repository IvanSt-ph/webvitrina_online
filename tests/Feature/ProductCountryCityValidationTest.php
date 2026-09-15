<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCountryCityValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_create_rejects_city_from_another_country(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$country, $cityFromAnotherCountry] = $this->mismatchedCountryAndCity();

        $payload = $this->validProductPayload([
            'country_id' => $country->id,
            'city_id' => $cityFromAnotherCountry->id,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city_id');
    }

    public function test_admin_product_update_rejects_city_from_another_country(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        [$country, $cityFromAnotherCountry] = $this->mismatchedCountryAndCity();

        $this->actingAs($admin)
            ->patchJson(route('admin.products.update', $product), [
                'country_id' => $country->id,
                'city_id' => $cityFromAnotherCountry->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city_id');

        $this->assertNotSame($cityFromAnotherCountry->id, $product->fresh()->city_id);
    }

    public function test_seller_product_create_rejects_city_from_another_country(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        [$country, $cityFromAnotherCountry] = $this->mismatchedCountryAndCity();

        $payload = $this->validProductPayload([
            'country_id' => $country->id,
            'city_id' => $cityFromAnotherCountry->id,
        ]);

        $this->actingAs($seller)
            ->postJson(route('seller.products.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city_id');
    }

    public function test_seller_product_update_rejects_city_from_another_country(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        [$country, $cityFromAnotherCountry] = $this->mismatchedCountryAndCity();

        $payload = $this->validProductPayload([
            'country_id' => $country->id,
            'city_id' => $cityFromAnotherCountry->id,
        ]);

        $this->actingAs($seller)
            ->patchJson(route('seller.products.update', $product), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city_id');

        $this->assertNotSame($cityFromAnotherCountry->id, $product->fresh()->city_id);
    }

    public function test_admin_form_update_rejects_mismatched_city_without_changing_saved_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(User::factory()->create(['role' => 'seller']));
        [$country, $city] = $this->mismatchedCountryAndCity();
        $originalCity = $product->city_id;

        $this->actingAs($admin)->withSession(['country_id' => $product->country_id, 'city_id' => $originalCity])
            ->patch(route('admin.products.update', $product), [
                'country_id' => $country->id,
                'city_id' => $city->id,
            ])
            ->assertSessionHasErrors('city_id')
            ->assertSessionHas('city_id', $originalCity);

        $this->assertEquals($originalCity, $product->fresh()->city_id);
    }

    public function test_method_spoofed_update_keeps_invalid_city_for_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(User::factory()->create(['role' => 'seller']));

        $this->actingAs($admin)->post(route('admin.products.update', $product), [
            '_method' => 'PATCH',
            'country_id' => $product->country_id,
            'city_id' => ['invalid'],
        ])->assertSessionHasErrors('city_id');
    }

    public function test_unrelated_update_does_not_inherit_or_clear_location_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(User::factory()->create(['role' => 'seller']));
        $otherLocation = $this->validProductPayload();
        $originalCity = $product->city_id;

        foreach ([[], ['clear_location' => 1]] as $extra) {
            $this->actingAs($admin)->withSession([
                'country_id' => $otherLocation['country_id'],
                'city_id' => $otherLocation['city_id'],
            ])->patch(route('admin.products.update', $product), ['title' => 'Updated title'] + $extra)
                ->assertRedirect(route('admin.products.index'))
                ->assertSessionHas('country_id', $otherLocation['country_id'])
                ->assertSessionHas('city_id', $otherLocation['city_id']);

            $this->assertEquals($originalCity, $product->fresh()->city_id);
            $this->assertSame('Updated title', $product->fresh()->title);
        }
    }

    public function test_product_forms_reject_arrays_containing_existing_location_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $payload = $this->validProductPayload(['user_id' => $seller->id]);

        foreach (['country_id', 'city_id'] as $field) {
            $invalid = array_replace($payload, [$field => [$payload[$field]]]);
            foreach ([['admin', $admin], ['seller', $seller]] as [$prefix, $user]) {
                $this->actingAs($user)->postJson(route($prefix.'.products.store'), $invalid)
                    ->assertUnprocessable()->assertJsonValidationErrors($field);
                $this->actingAs($user)->patchJson(route($prefix.'.products.update', $product), $invalid)
                    ->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
    }

    private function mismatchedCountryAndCity(): array
    {
        $country = Country::create(['name' => 'Moldova ' . uniqid()]);
        $anotherCountry = Country::create(['name' => 'Ukraine ' . uniqid()]);
        $cityFromAnotherCountry = City::create([
            'country_id' => $anotherCountry->id,
            'name' => 'Odesa ' . uniqid(),
        ]);

        return [$country, $cityFromAnotherCountry];
    }

    private function validProductPayload(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $country = Country::create(['name' => 'Valid country ' . uniqid()]);
        $city = City::create(['country_id' => $country->id, 'name' => 'Valid city ' . uniqid()]);
        $seller = User::factory()->create(['role' => 'seller']);

        return array_merge([
            'title' => 'Country city test product ' . uniqid(),
            'sku' => 'CC-' . uniqid(),
            'price' => 100,
            'stock' => 10,
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'description' => 'Valid product',
            'status' => 'draft',
        ], $overrides);
    }

    private function createProduct(User $seller): Product
    {
        return Product::create($this->validProductPayload(['user_id' => $seller->id]));
    }
}
