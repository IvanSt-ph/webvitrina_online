<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ColorController;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\City;
use App\Models\Color;
use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use App\Services\AttributeDuplicateCleanup;
use App\Services\AttributeService;
use App\Services\ProductService;
use Database\Seeders\CategoryAttributesSeederRevised;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Tests\Support\TestDatabaseSafety;
use Tests\Support\AttributeFeatureTestCase;

class ProductAttributeValidationTest extends AttributeFeatureTestCase
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
            $attribute = Attribute::create(['name' => 'Audit '.$type, 'type' => $type, 'options' => $type === 'select' ? ['S', 'M', '0'] : null]);
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
        self::assertSame(0, $product->attributeValues()->whereNull('archived_at')->count());
        self::assertSame(4, $product->attributeValues()->whereNotNull('archived_at')->count());
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
            ['0'.$this->attributes['text']->id => 'Aliased key'],
            [$this->attributes['text']->id.'.nested' => 'Nested key'],
        ];
        foreach ($cases as $values) {
            try {
                $service->sync($product, $values);
                self::fail('Invalid values accepted: '.json_encode($values));
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
        $payload = [
            'title' => 'Audit product',
            'price' => 10,
            'stock' => 1,
            'original_stock' => 1,
            'status' => 'draft',
            'user_id' => $seller->id,
            'category_id' => $this->category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
        ];
        foreach (['seller' => $seller, 'admin' => $admin] as $prefix => $user) {
            $bad = $payload + ['attributes' => $this->values(['number' => 'not-a-number'])];
            $this->actingAs($user)->postJson(route($prefix.'.products.store'), $bad)
                ->assertUnprocessable()->assertJsonValidationErrors('attributes.'.$this->attributes['number']->id);
            $this->patchJson(route($prefix.'.products.update', $product), $bad)
                ->assertUnprocessable()->assertJsonValidationErrors('attributes.'.$this->attributes['number']->id);
            $this->postJson(route($prefix.'.products.store'), $payload + ['attributes' => 'bad'])
                ->assertUnprocessable()->assertJsonValidationErrors('attributes');
            $valid = $this->values(['text' => '<ordinary> & text', 'number' => '0', 'select' => 'M', 'color' => (string) $this->color->id]);
            $this->patch(route($prefix.'.products.update', $product), $payload + ['attributes' => $valid])
                ->assertSessionHasNoErrors()->assertRedirect();
            self::assertEquals($valid, $product->attributeValues()->pluck('value', 'attribute_id')->all());
            $this->post(route($prefix.'.products.store'), $payload + ['attributes' => $valid])
                ->assertSessionHasNoErrors()->assertRedirect();
            $created = Product::where('user_id', $seller->id)->latest('id')->firstOrFail();
            self::assertEquals($valid, $created->attributeValues()->pluck('value', 'attribute_id')->all());
        }
    }

    public function test_category_changes_archive_old_attributes_and_return_does_not_restore_them(): void
    {
        $product = $this->product();
        $values = $this->values(['text' => 'Original']);
        app(AttributeService::class)->sync($product, $values);
        $service = app(ProductService::class);
        $service->update($product, ['title' => 'Changed title']);
        self::assertEquals($values, $product->attributeValues()->pluck('value', 'attribute_id')->all());
        $newCategory = Category::factory()->create();
        $service->update($product, ['category_id' => $newCategory->id]);
        self::assertSame(1, $product->attributeValues()->count());
        self::assertSame(0, $product->fresh()->attributes()->count());
        $service->update($product, ['category_id' => $this->category->id]);
        self::assertSame(0, $product->fresh()->attributes()->count());
        self::assertNull(app(AttributeService::class)->getForProduct($product->fresh())->firstWhere('id', $this->attributes['text']->id)->value);
        $service->update($product, [], attrs: $values);
        self::assertSame(1, $product->fresh()->attributes()->count());
        self::assertSame(2, $product->attributeValues()->count());
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
        self::assertEquals($this->values(['text' => 'Shared']), $product->attributeValues()->whereNull('archived_at')->pluck('value', 'attribute_id')->all());
        self::assertSame(1, $product->attributeValues()->whereNotNull('archived_at')->count());
    }

    public function test_sync_preserves_historical_values_after_attribute_is_detached(): void
    {
        $product = $this->product();

        $text = $this->attributes['text'];
        $number = $this->attributes['number'];

        $service = app(AttributeService::class);

        $service->sync($product, [
            $text->id => 'Историческое значение',
            $number->id => '10',
        ]);

        // Характеристика больше не принадлежит категории,
        // но сохранённое значение товара остаётся.
        $this->category->attributes()->detach($text->id);

        $service->sync($product, [
            $number->id => '20',
        ]);

        self::assertDatabaseHas('attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $text->id,
            'value' => 'Историческое значение',
        ]);

        self::assertDatabaseHas('attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $number->id,
            'value' => '20',
        ]);
        self::assertNotNull($product->attributeValues()->where('attribute_id', $text->id)->first()->archived_at);
    }

    #[\PHPUnit\Framework\Attributes\Group('attribute-seeder')]
    public function test_revised_seeder_is_idempotent_and_preserves_every_value_row(): void
    {
        if (getenv('WV_ALLOW_ATTRIBUTE_SEEDER_TEST') !== '1') {
            $this->markTestSkipped('Seeder regression requires explicit WV_ALLOW_ATTRIBUTE_SEEDER_TEST=1 authorization.');
        }
        $this->category->update(['slug' => 'smartfony']);
        $product = $this->product();
        app(AttributeService::class)->sync($product, $this->values(['text' => 'Historical', 'number' => '12.5']));
        $before = $product->attributeValues()->get(['id', 'attribute_id', 'value'])->toArray();
        (new CategoryAttributesSeederRevised)->run();
        $definitions = DB::table('attributes')->orderBy('id')->get()->toJson();
        $links = DB::table('attribute_category')->orderBy('id')->get()->toJson();
        $values = DB::table('attribute_values')->orderBy('id')->get()->toJson();
        (new CategoryAttributesSeederRevised)->run();
        self::assertSame($definitions, DB::table('attributes')->orderBy('id')->get()->toJson());
        self::assertSame($links, DB::table('attribute_category')->orderBy('id')->get()->toJson());
        self::assertSame($values, DB::table('attribute_values')->orderBy('id')->get()->toJson());
        self::assertSame($before, $product->attributeValues()->get(['id', 'attribute_id', 'value'])->toArray());
        self::assertSame(2, $product->attributeValues()->whereNotNull('archived_at')->count());
    }

    public function test_admin_detach_keeps_history_and_reattach_does_not_reactivate(): void
    {
        $product = $this->product();
        $attribute = $this->attributes['text'];
        app(AttributeService::class)->sync($product, [$attribute->id => 'Saved']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete(route('admin.categories.attributes.destroy', [$this->category, $attribute]))->assertRedirect()->assertSessionHasNoErrors();
        self::assertNotNull($attribute->fresh());
        self::assertSame(1, $product->attributeValues()->whereNotNull('archived_at')->count());
        $this->category->attributes()->attach($attribute);
        self::assertSame(0, $product->attributes()->count());
        self::assertNull(app(AttributeService::class)->getForProduct($product)->firstWhere('id', $attribute->id)->value);
    }

    public function test_definition_update_is_category_local_and_noop_does_not_create_duplicates(): void
    {
        $attribute = $this->attributes['select'];
        $other = Category::factory()->create();
        $other->attributes()->attach($attribute);
        $product = $this->product();
        app(AttributeService::class)->sync($product, [$attribute->id => 'S']);
        $admin = User::factory()->create(['role' => 'admin']);
        $data = ['name' => $attribute->name, 'type' => 'select', 'options' => 'S,M,0,XL', 'is_filterable' => 1];
        $this->actingAs($admin)->put(route('admin.categories.attributes.update', [$this->category, $attribute]), $data)->assertRedirect();
        self::assertSame(['S', 'M', '0'], $attribute->fresh()->options);
        self::assertTrue($other->attributes()->whereKey($attribute->id)->exists());
        self::assertSame(1, $product->attributeValues()->whereNotNull('archived_at')->count());
        $replacement = $this->category->attributes()->where('name', $attribute->name)->firstOrFail();
        $count = Attribute::count();
        $this->put(route('admin.categories.attributes.update', [$this->category, $replacement]), $data)->assertRedirect();
        self::assertSame($count, Attribute::count());
    }

    public function test_fk_prevents_deleting_definition_referenced_by_history(): void
    {
        $product = $this->product();
        app(AttributeService::class)->sync($product, $this->values(['text' => 'Protected']));
        app(AttributeService::class)->sync($product, $this->values(['text' => '']));
        try {
            DB::table('attributes')->where('id', $this->attributes['text']->id)->delete();
            self::fail('Foreign key allowed historical value deletion');
        } catch (QueryException $exception) {
            self::assertSame('23000', $exception->errorInfo[0]);
        }
        self::assertSame(1, $product->attributeValues()->count());
    }

    public function test_public_views_and_both_filter_routes_exclude_archived_values(): void
    {
        $product = $this->product();
        $product->update(['status' => 'active']);
        $number = $this->attributes['number'];
        $number->update(['is_filterable' => true, 'unit' => 'кг']);
        app(AttributeService::class)->sync($product, [$number->id => '12.5']);
        $filters = ['filters' => [$number->id => ['from' => '12', 'to' => '13']]];
        foreach (['category.show', 'category.ajax'] as $route) {
            $this->get(route($route, ['slug' => $this->category->slug] + $filters))->assertOk()->assertSee($product->title);
        }
        app(AttributeService::class)->sync($product, [$number->id => '']);
        foreach (['category.show', 'category.ajax'] as $route) {
            $this->get(route($route, ['slug' => $this->category->slug] + $filters))->assertOk()->assertDontSee($product->title);
        }
        self::assertSame(0, $product->fresh()->attributes->count());
        self::assertStringNotContainsString('12.5', view('components.product.specs', ['product' => $product->fresh()])->render());
    }

    public function test_partial_edit_preserves_other_active_values_and_validation_rolls_back_category(): void
    {
        $product = $this->product();
        $service = app(AttributeService::class);
        $service->sync($product, $this->values(['text' => 'Keep', 'number' => '10']));
        $service->sync($product, $this->values(['number' => '11']));
        self::assertSame('Keep', $product->attributeValues()->whereNull('archived_at')->where('attribute_id', $this->attributes['text']->id)->value('value'));
        $destination = Category::factory()->create();
        try {
            app(ProductService::class)->update($product, ['category_id' => $destination->id], attrs: $this->values(['text' => 'Wrong category']));
            self::fail('Invalid category payload accepted');
        } catch (ValidationException) {
            self::assertSame($this->category->id, $product->fresh()->category_id);
            self::assertSame(2, $product->attributeValues()->whereNull('archived_at')->count());
        }
    }

    public function test_duplicate_dry_run_and_reviewed_merge_preserve_value_ids_and_history(): void
    {
        $original = $this->attributes['text'];
        $duplicate = Attribute::create($original->fresh()->only(['name', 'type', 'unit', 'options', 'is_filterable']));
        $product = $this->product();
        $rowId = DB::table('attribute_values')->insertGetId(['product_id' => $product->id, 'attribute_id' => $duplicate->id,
            'value' => 'Never restore automatically', 'created_at' => now(), 'updated_at' => now()]);
        $service = app(AttributeDuplicateCleanup::class);
        $snapshot = $service->snapshot();
        self::assertSame(1, $snapshot['report']['candidate_attribute_removals_after_review']);
        self::assertNotNull($duplicate->fresh());
        self::assertSame(1, $service->apply($snapshot['fingerprint']));
        $row = DB::table('attribute_values')->where('id', $rowId)->first();
        self::assertSame($original->id, $row->attribute_id);
        self::assertSame('Never restore automatically', $row->value);
        self::assertNotNull($row->archived_at);
        self::assertSame(0, $product->fresh()->attributes()->count());
        self::assertSame(0, $service->snapshot()['report']['candidate_attribute_removals_after_review']);
    }

    public function test_duplicate_conflicts_and_stale_review_are_rejected(): void
    {
        $original = $this->attributes['text'];
        $duplicate = Attribute::create($original->fresh()->only(['name', 'type', 'unit', 'options', 'is_filterable']));
        $product = $this->product();
        foreach ([$original->id, $duplicate->id] as $id) {
            DB::table('attribute_values')->insert(['product_id' => $product->id, 'attribute_id' => $id, 'value' => 'Keep both rows']);
        }
        $service = app(AttributeDuplicateCleanup::class);
        $snapshot = $service->snapshot();
        self::assertSame(0, $snapshot['report']['candidate_attribute_removals_after_review']);
        self::assertContains('overlapping_product_values', $snapshot['report']['duplicate_groups'][0]['conflicts']);
        $duplicate->update(['unit' => 'см']);
        try {
            $service->apply($snapshot['fingerprint']);
            self::fail('Stale plan applied');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('Data changed', $exception->getMessage());
        }
        self::assertSame(2, $product->attributeValues()->count());
    }

    public function test_forms_include_units_fractional_numbers_and_preserve_order(): void
    {
        $number = $this->attributes['number'];
        $number->update(['unit' => 'мм']);
        DB::table('attribute_category')->where('category_id', $this->category->id)->update(['sort_order' => 10]);
        $this->category->attributes()->updateExistingPivot($number->id, ['sort_order' => 0]);
        $attributes = app(AttributeService::class)->getByCategory($this->category->id);
        self::assertSame($number->id, $attributes->first()->id);
        foreach (['seller.products.partials.attributes', 'admin.products.partials.attributes'] as $template) {
            $html = view($template, ['attributes' => $attributes, 'errors' => new ViewErrorBag])->render();
            self::assertStringContainsString('(мм)', $html);
            self::assertStringContainsString('step="any"', $html);
        }
    }

    public function test_used_color_cannot_be_deleted_or_reinterpreted(): void
    {
        $product = $this->product();
        app(AttributeService::class)->sync($product, $this->values(['color' => (string) $this->color->id]));
        app(AttributeService::class)->sync($product, $this->values(['color' => '']));
        $controller = app(ColorController::class);
        $controller->destroy($this->color);
        self::assertNotNull($this->color->fresh());
        $request = Request::create('/', 'PUT', ['name' => 'Changed historical color', 'hex' => '#987654']);
        $controller->update($request, $this->color);
        self::assertSame('Audit blue', $this->color->fresh()->name);
        self::assertSame('#123456', $this->color->fresh()->hex);
        self::assertSame(1, $product->attributeValues()->whereNotNull('archived_at')->count());
    }

    public function test_color_selection_renders_and_catalog_filter_ignores_archived_values(): void
    {
        $service = app(AttributeService::class);
        $product = $this->product();
        $attribute = $this->attributes['color'];
        $attribute->update(['is_filterable' => 1]);
        $service->sync($product, $this->values(['color' => (string) $this->color->id]));
        $html = view('seller.products.partials.attributes', [
            'attributes' => $service->getForProduct($product), 'product' => $product,
            'errors' => new ViewErrorBag,
        ])->render();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $radio = $xpath->query('//input[@type="radio" and @name="attributes['.$attribute->id.']" and @checked]');
        self::assertSame(1, $radio->length);
        self::assertSame((string) $this->color->id, $radio->item(0)->getAttribute('value'));
        \App\Services\CategoryFilterCacheService::clearFor($this->category);
        $request = Request::create('/', 'GET', ['filters' => [$attribute->id => [(string) $this->color->id, '999999999']]]);
        \App\Support\CatalogAttributeFilters::normalize($request, $this->category);
        self::assertSame([$attribute->id => [(string) $this->color->id]], $request->query('filters'));
        $query = Product::whereKey($product->id);
        \App\Support\CatalogAttributeFilters::apply($query, $request->query('filters'));
        self::assertTrue($query->exists());
        $service->sync($product, $this->values(['color' => '']));
        self::assertFalse($query->exists());
        self::assertSame(1, $product->attributeValues()->where('attribute_id', $attribute->id)->whereNotNull('archived_at')->count());
    }

    public function test_admin_rejects_wrong_type_for_exact_color_name_before_mutation(): void
    {
        $request = Request::create('/', 'POST', ['name' => 'Цвет', 'type' => 'text']);
        $this->expectException(ValidationException::class);
        app(\App\Http\Controllers\Admin\CategoryAttributeController::class)->store($request, $this->category);
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
