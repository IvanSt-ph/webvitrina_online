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
