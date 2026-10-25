<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\City;
use App\Models\Color;
use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use App\Services\AttributeService;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\TestDatabaseSafety;
use Tests\TestCase;

class ProductAttributeValidationTest extends TestCase
{
    private Category $category;
    private array $attributes;
    private Color $color;

    protected function setUp(): void
    {
        parent::setUp();
        // Existing schema only: no RefreshDatabase, migrations or truncation.
        $connection = DB::connection();
        TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
        DB::beginTransaction();
        $this->rollBackTestTransactionsBeforeApplicationDestroyed();
        $this->category = Category::factory()->create();
        $this->attributes = [];
        foreach (['text', 'number', 'select', 'color'] as $type) {
            $attribute = Attribute::create(['name' => 'Audit ' . $type, 'type' => $type, 'options' => $type === 'select' ? ['S', 'M', '0'] : null]);
            $this->category->attributes()->attach($attribute);
            $this->attributes[$type] = $attribute;
        }
        $this->color = Color::create(['name' => 'Audit blue', 'hex' => '#123456']);
        $this->attributes['color']->colors()->attach($this->color);
    }

    public function test_valid_values_round_trip_and_optional_values_can_be_cleared(): void
    {
        $product = $this->product();
        $values = $this->values(['text' => 'Текст <>&"\' [x](/audit)', 'number' => '-12.5', 'select' => '0', 'color' => (string) $this->color->id]);
        $service = app(AttributeService::class);
        $long = $this->values(['text' => str_repeat('я', 255)]);
        self::assertSame($long, $service->validateValues($long, $this->category->id));
        $service->sync($product, $service->validateValues($values, $this->category->id));
        self::assertEquals($values, $product->attributeValues()->pluck('value', 'attribute_id')->all());
        $service->sync($product, $this->values(['text' => null, 'number' => '', 'select' => '', 'color' => null]));
        self::assertSame(0, $product->attributeValues()->count());
    }

    public function test_invalid_values_are_rejected_before_existing_values_are_deleted(): void
    {
        $product = $this->product();
        $service = app(AttributeService::class);
        $original = $this->values(['text' => 'Original']);
        $service->sync($product, $original);
        $foreign = Attribute::create(['name' => 'Foreign', 'type' => 'text']);
        $foreignColor = Color::create(['name' => 'Foreign color', 'hex' => '#654321']);
        $cases = [
            $this->values(['text' => str_repeat('я', 256)]),
            $this->values(['text' => ['nested']]),
            $this->values(['text' => 123]),
            $this->values(['number' => 'not-a-number']),
            $this->values(['number' => '1e999']),
            $this->values(['number' => true]),
            $this->values(['select' => 'not-in-options']),
            $this->values(['select' => ['S']]),
            $this->values(['color' => (string) $foreignColor->id]),
            [$foreign->id => 'Foreign value'],
            ['0' . $this->attributes['text']->id => 'Aliased key'],
            [$this->attributes['text']->id . '.nested' => 'Nested key'],
        ];
        foreach ($cases as $values) {
            try {
                $service->sync($product, $values);
                self::fail('Invalid values accepted: ' . json_encode($values));
            } catch (ValidationException $e) {
                self::assertNotEmpty($e->errors());
            }
            self::assertEquals($original, $product->attributeValues()->pluck('value', 'attribute_id')->all());
        }
    }

    public function test_invalid_root_structure_is_a_validation_error(): void
    {
        foreach (['text', 1, true, ['value']] as $invalid) {
            try {
                app(AttributeService::class)->validateValues($invalid, $this->category->id);
                self::fail('Invalid structure accepted');
            } catch (ValidationException $e) {
                self::assertArrayHasKey('attributes', $e->errors());
            }
        }
        self::assertSame([], app(AttributeService::class)->validateValues(null, $this->category->id));
    }

    public function test_both_http_entry_points_validate_create_and_update(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product($seller);
        $country = Country::create(['name' => 'Audit country']);
        $city = City::create(['country_id' => $country->id, 'name' => 'Audit city']);
        $payload = ['title' => 'Audit product', 'price' => 10, 'stock' => 1, 'status' => 'draft',
            'user_id' => $seller->id, 'category_id' => $this->category->id,
            'country_id' => $country->id, 'city_id' => $city->id];
        foreach (['seller' => $seller, 'admin' => $admin] as $prefix => $user) {
            $bad = $payload + ['attributes' => $this->values(['number' => 'not-a-number'])];
            $this->actingAs($user)->postJson(route($prefix . '.products.store'), $bad)
                ->assertUnprocessable()->assertJsonValidationErrors('attributes.' . $this->attributes['number']->id);
            $this->patchJson(route($prefix . '.products.update', $product), $bad)
                ->assertUnprocessable()->assertJsonValidationErrors('attributes.' . $this->attributes['number']->id);
            $this->postJson(route($prefix . '.products.store'), $payload + ['attributes' => 'bad'])
                ->assertUnprocessable()->assertJsonValidationErrors('attributes');
            $valid = $this->values(['text' => '<ordinary> & text', 'number' => '0', 'select' => 'M', 'color' => (string) $this->color->id]);
            $this->patch(route($prefix . '.products.update', $product), $payload + ['attributes' => $valid])
                ->assertSessionHasNoErrors()->assertRedirect();
            self::assertEquals($valid, $product->attributeValues()->pluck('value', 'attribute_id')->all());
            $this->post(route($prefix . '.products.store'), $payload + ['attributes' => $valid])
                ->assertSessionHasNoErrors()->assertRedirect();
            $created = Product::where('user_id', $seller->id)->latest('id')->firstOrFail();
            self::assertEquals($valid, $created->attributeValues()->pluck('value', 'attribute_id')->all());
        }
    }

    public function test_category_changes_clear_old_attributes_but_partial_edits_keep_them(): void
    {
        $product = $this->product();
        $values = $this->values(['text' => 'Original']);
        app(AttributeService::class)->sync($product, $values);
        $service = app(ProductService::class);
        $service->update($product, ['title' => 'Changed title']);
        self::assertEquals($values, $product->attributeValues()->pluck('value', 'attribute_id')->all());
        $newCategory = Category::factory()->create();
        $service->update($product, ['category_id' => $newCategory->id]);
        self::assertSame(0, $product->attributeValues()->count());
    }

    public function test_category_change_with_gallery_edit_keeps_only_shared_valid_attributes(): void
    {
        $product = $this->product();
        // No file is created; deletion is deferred and the test rolls back.
        $path = 'products/audit/medium/unwritten.webp';
        $product->update(['gallery' => [$path]]);
        app(AttributeService::class)->sync($product, $this->values(['text' => 'Shared', 'number' => '42']));
        $destination = Category::factory()->create();
        $destination->attributes()->attach($this->attributes['text']);

        app(ProductService::class)->update($product, ['category_id' => $destination->id], galleryToDelete: [$path]);

        self::assertSame([], $product->fresh()->gallery);
        self::assertEquals($this->values(['text' => 'Shared']), $product->attributeValues()->pluck('value', 'attribute_id')->all());
    }

    private function values(array $values): array
    {
        $result = [];
        foreach ($values as $type => $value) {
            $result[$this->attributes[$type]->id] = $value;
        }
        return $result;
    }

    private function product(?User $seller = null): Product
    {
        $seller ??= User::factory()->create(['role' => 'seller']);
        return Product::create(['title' => 'Attribute audit', 'user_id' => $seller->id,
            'category_id' => $this->category->id, 'price' => 10, 'stock' => 1, 'status' => 'draft']);
    }
}
