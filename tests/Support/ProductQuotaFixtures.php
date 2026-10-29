<?php

namespace Tests\Support;

use App\Models\{Category, City, Country, Product, User};

trait ProductQuotaFixtures
{
    private function quotaFixture(int $used = 9, string $plan = 'starter'): array
    {
        $seller = User::factory()->seller()->create(['seller_plan' => $plan]);
        $country = Country::create(['name' => 'Quota country', 'currency' => 'MDL']);
        $city = City::create(['name' => 'Quota city', 'country_id' => $country->id]);
        $data = [
            'user_id' => $seller->id, 'title' => 'Quota product '.$seller->id, 'price' => 100,
            'price_prb' => 100, 'price_mdl' => 100, 'price_uah' => 100, 'currency_base' => 'MDL',
            'stock' => 1, 'status' => Product::STATUS_ACTIVE,
            'category_id' => Category::factory()->create()->id, 'country_id' => $country->id, 'city_id' => $city->id,
        ];
        for ($i = 0; $i < $used; $i++) {
            Product::create(array_merge($data, ['title' => 'Existing '.$seller->id.' '.$i,
                'status' => [Product::STATUS_ACTIVE, Product::STATUS_DRAFT, Product::STATUS_BLOCKED][$i % 3]]));
        }
        return [$seller, $data];
    }
}
