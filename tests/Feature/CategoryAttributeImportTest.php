<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\User;
use App\Services\AttributeImport\ImportException;
use App\Services\AttributeImport\ImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\AttributeImportWorkbook;
use Tests\Support\TestDatabaseSafety;
use Tests\Support\AttributeFeatureTestCase;

final class CategoryAttributeImportTest extends AttributeFeatureTestCase
{
    private Category $category;

    private ImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $connection = DB::connection();
        TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
        config(['attribute_import.allowed_database' => 'webv3_testing']);
        DB::beginTransaction();
        $this->rollBackTestTransactionsBeforeApplicationDestroyed();
        $this->category = Category::factory()->create();
        $this->service = new ImportService;
    }

    private function row(array $changes = []): array
    {
        return array_replace(['category_slug' => $this->category->slug, 'group_name' => 'Общие', 'attribute_name' => 'Import test '.$this->category->id, 'type' => 'text', 'unit' => '', 'is_filterable' => '0', 'options' => '', 'sort_order' => '12'], $changes);
    }

    private function applyRows(array $rows): array
    {
        $report = $this->service->preview($rows);
        self::assertFalse($report['blocked'], json_encode($report['rows']));

        return $this->service->apply($rows, $report['fingerprint']);
    }

    public function test_new_definition_and_repeat_are_idempotent_and_preview_does_not_write(): void
    {
        $rows = [2 => $this->row()];
        $before = Attribute::count();
        $report = $this->service->preview($rows);
        self::assertSame($before, Attribute::count());
        self::assertSame('CREATE_AND_LINK', $report['rows'][0]['status']);
        $this->service->apply($rows, $report['fingerprint']);
        self::assertSame($before + 1, Attribute::count());
        self::assertSame('UNCHANGED', $this->applyRows($rows)['rows'][0]['status']);
        self::assertSame(1, $this->category->attributes()->count());
    }

    public function test_color_import_repeat_conflict_and_catalog_drift(): void
    {
        $name = 'Палитра '.bin2hex(random_bytes(5));
        $color = Color::create(['name' => $name, 'hex' => '#123456']);
        $row = $this->row(['type' => 'color', 'is_filterable' => '1', 'colors' => json_encode([$name])]);
        $this->applyRows([2 => $row]);
        self::assertSame('UNCHANGED', $this->applyRows([2 => $row])['rows'][0]['status']);
        $attribute = $this->category->attributes()->first();
        self::assertSame([$color->id], $attribute->colors()->pluck('colors.id')->all());
        self::assertEquals(1, $attribute->is_filterable);
        self::assertSame('CONFLICT', $this->service->preview([2 => $this->row()])['rows'][0]['status']);
        $plan = $this->service->preview([2 => $row]);
        $color->update(['hex' => '#654321']);
        $this->expectException(ImportException::class);
        $this->service->apply([2 => $row], $plan['fingerprint']);
    }

    public function test_exact_color_name_requires_color_without_affecting_other_names(): void
    {
        self::assertTrue($this->service->preview([2 => $this->row(['attribute_name' => 'Цвет'])])['blocked']);
        self::assertFalse($this->service->preview([2 => $this->row(['attribute_name' => 'Цвет корпуса'])])['blocked']);
    }

    public function test_standard_fifteen_color_import_repeats_without_duplicates_and_rejects_subsets(): void
    {
        foreach (\Tests\Support\ApprovedColorPalette::catalog() as $color) {
            Color::create(['name' => $color['name'], 'hex' => $color['hex']]);
        }
        $row = $this->row(['attribute_name' => 'Цвет', 'type' => 'color', 'is_filterable' => '1',
            'colors' => json_encode(\Tests\Support\ApprovedColorPalette::NAMES, JSON_UNESCAPED_UNICODE)]);
        $this->applyRows([2 => $row]);
        $id = $this->category->attributes()->first()->id;
        $definitions = Attribute::count();
        $links = DB::table('attribute_color')->orderBy('id')->get()->toArray();
        self::assertCount(15, $links);
        self::assertSame('UNCHANGED', $this->applyRows([2 => $row])['rows'][0]['status']);
        self::assertSame($id, $this->category->attributes()->first()->id);
        self::assertSame($definitions, Attribute::count());
        self::assertEquals($links, DB::table('attribute_color')->orderBy('id')->get()->toArray());
        $row['colors'] = '["Синий"]';
        self::assertSame('INVALID', $this->service->preview([2 => $row])['rows'][0]['status']);
        $row['attribute_name'] = 'Цвет корпуса';
        self::assertFalse($this->service->preview([2 => $row])['blocked']);
    }

    public function test_existing_definition_is_reused_and_sort_order_is_preserved(): void
    {
        $data = $this->service->preview([2 => $this->row()])['rows'][0]['data'];
        $attribute = Attribute::create($data);
        self::assertSame('ADD_LINK', $this->applyRows([2 => $this->row()])['rows'][0]['status']);
        $this->applyRows([2 => $this->row(['sort_order' => '99'])]);
        self::assertSame(12, DB::table('attribute_category')->where('category_id', $this->category->id)->where('attribute_id', $attribute->id)->value('sort_order'));
    }

    #[DataProvider('invalidRows')]
    public function test_invalid_rows_block_entire_import(array $changes, string $status): void
    {
        $rows = [2 => $this->row(['attribute_name' => 'Valid unique '.$this->category->id]), 3 => $this->row($changes)];
        $report = $this->service->preview($rows);
        self::assertTrue($report['blocked']);
        self::assertSame($status, $report['rows'][1]['status']);
        self::assertNotEmpty($report['rows'][1]['errors']);
        $before = Attribute::count();
        try {
            $this->service->apply($rows, $report['fingerprint']);
            self::fail('Must reject');
        } catch (\RuntimeException) {
            self::assertSame($before, Attribute::count());
        }
    }

    public static function invalidRows(): array
    {
        return [
            [['category_slug' => 'does-not-exist-import-test'], 'INVALID'],
            [['type' => 'multiselect'], 'UNSUPPORTED'],
            [['type' => 'color'], 'INVALID'],
            [['is_filterable' => 'yes'], 'INVALID'],
            [['type' => 'color', 'unit' => 'cm', 'colors' => '["Синий"]'], 'INVALID'],
            [['type' => 'color', 'options' => '["Синий"]', 'colors' => '["Синий"]'], 'INVALID'],
            [['colors' => '[]'], 'INVALID'],
            [['type' => 'color', 'colors' => '["Нет такого цвета импорта"]'], 'INVALID'],
            [['sort_order' => '-1'], 'INVALID'],
            [['attribute_name' => ''], 'INVALID'],
            [['type' => 'select', 'options' => '["S","s"]'], 'INVALID'],
            [['type' => 'select', 'options' => '[1,2]'], 'INVALID'],
        ];
    }

    public function test_options_and_unit_conflicts_are_blocked(): void
    {
        foreach ([['type' => 'select', 'options' => '["A","B"]'], ['type' => 'number', 'unit' => 'kg']] as $i => $changes) {
            $row = $this->row(['attribute_name' => 'Conflict '.$this->category->id.' '.$i, ...$changes]);
            $this->applyRows([2 => $row]);
            $row[$i === 0 ? 'options' : 'unit'] = $i === 0 ? '["A","C"]' : 'cm';
            $report = $this->service->preview([2 => $row]);
            self::assertSame('CONFLICT', $report['rows'][0]['status']);
            $this->assertApplyRejectedWithoutChanges([2 => $row], $report);
        }
    }

    public function test_duplicate_rows_in_one_category_are_invalid_even_with_different_signatures(): void
    {
        foreach (['text', 'number'] as $type) {
            $rows = [2 => $this->row(), 8 => $this->row(['type' => $type])];
            $report = $this->service->preview($rows);
            self::assertSame(2, $report['counts']['INVALID']);
            $this->assertApplyRejectedWithoutChanges($rows, $report);
        }
    }

    private function assertApplyRejectedWithoutChanges(array $rows, array $report): void
    {
        $definitions = Attribute::orderBy('id')->get()->toArray();
        $links = DB::table('attribute_category')->orderBy('id')->get()->toArray();
        $values = DB::table('attribute_values')->orderBy('id')->get()->toArray();
        try {
            $this->service->apply($rows, $report['fingerprint']);
            self::fail('Blocked plan must not apply');
        } catch (ImportException) {
            self::assertEquals($definitions, Attribute::orderBy('id')->get()->toArray());
            self::assertEquals($links, DB::table('attribute_category')->orderBy('id')->get()->toArray());
            self::assertEquals($values, DB::table('attribute_values')->orderBy('id')->get()->toArray());
        }
    }

    public function test_different_definitions_in_different_categories_apply_and_repeat_without_duplicates(): void
    {
        $other = Category::factory()->create();
        $rows = [2 => $this->row(), 8 => $this->row(['category_slug' => $other->slug, 'type' => 'select', 'options' => '["S","M"]'])];
        $before = Attribute::count();
        $report = $this->applyRows($rows);
        self::assertSame(2, $report['counts']['CREATE_AND_LINK']);
        self::assertSame(2, $report['new_definitions']);
        self::assertSame($before + 2, Attribute::count());
        $first = $this->category->attributes()->first();
        $second = $other->attributes()->first();
        self::assertNotSame($first->id, $second->id);
        self::assertSame('text', $first->type);
        self::assertSame(['S', 'M'], $second->options);
        self::assertSame(2, $this->applyRows($rows)['counts']['UNCHANGED']);
        self::assertSame($before + 2, Attribute::count());
        self::assertSame(1, $this->category->attributes()->count());
        self::assertSame(1, $other->attributes()->count());
    }

    public function test_foreign_different_definition_allows_creation_and_exact_reuse(): void
    {
        $other = Category::factory()->create();
        $foreign = $this->row(['category_slug' => $other->slug, 'type' => 'number', 'unit' => 'cm']);
        $this->applyRows([2 => $foreign]);
        $original = $other->attributes()->first()->toArray();
        self::assertSame('CREATE_AND_LINK', $this->applyRows([2 => $this->row()])['rows'][0]['status']);
        self::assertSame($original, $other->attributes()->first()->toArray());
        $third = Category::factory()->create();
        $before = Attribute::count();
        self::assertSame('ADD_LINK', $this->applyRows([2 => $this->row(['category_slug' => $third->slug])])['rows'][0]['status']);
        self::assertSame($before, Attribute::count());
        self::assertSame($this->category->attributes()->first()->id, $third->attributes()->first()->id);
    }

    #[DataProvider('attachedDefinitions')]
    public function test_category_local_conflicts_block_apply(array $changes, bool $attachExact): void
    {
        $rows = [2 => $this->row()];
        $data = $this->service->preview($rows)['rows'][0]['data'];
        if ($attachExact) {
            $this->category->attributes()->attach(Attribute::create($data)->id);
        }
        $changed = array_replace($data, $changes);
        if (isset($changes['normalized_name'])) {
            unset($changed['normalized_name']);
            $changed['name'] = mb_strtoupper(str_replace(' ', '  ', $data['name']));
        }
        $this->category->attributes()->attach(Attribute::create($changed)->id);
        $report = $this->service->preview($rows);
        self::assertSame('CONFLICT', $report['rows'][0]['status']);
        self::assertNotEmpty($report['rows'][0]['errors']);
        $this->assertApplyRejectedWithoutChanges($rows, $report);
    }

    public static function attachedDefinitions(): array
    {
        return [
            'different type' => [['type' => 'number'], false],
            'different unit' => [['unit' => 'cm'], false],
            'different filter' => [['is_filterable' => true], false],
            'two identical definitions' => [[], true],
            'exact plus different' => [['type' => 'number'], true],
            'normalized name collision' => [['normalized_name' => true], true],
        ];
    }

    public function test_color_bound_definition_is_never_reused_and_blocks_its_category(): void
    {
        $rows = [2 => $this->row()];
        $data = $this->service->preview($rows)['rows'][0]['data'];
        $colored = Attribute::create($data);
        $color = Color::create(['name' => 'Import regression', 'hex' => '#123456']);
        $colored->colors()->attach($color->id);
        $other = Category::factory()->create();
        $other->attributes()->attach($colored->id);
        self::assertSame('CREATE_AND_LINK', $this->applyRows($rows)['rows'][0]['status']);
        self::assertNotSame($colored->id, $this->category->attributes()->first()->id);
        self::assertSame([$color->id], $colored->colors()->pluck('colors.id')->all());
        $rows = [2 => $this->row(['category_slug' => $other->slug])];
        $report = $this->service->preview($rows);
        self::assertSame('CONFLICT', $report['rows'][0]['status']);
        $this->assertApplyRejectedWithoutChanges($rows, $report);
    }

    public function test_changed_plan_requires_new_confirmation(): void
    {
        $rows = [2 => $this->row()];
        $report = $this->service->preview($rows);
        $this->applyRows($rows);
        $this->expectExceptionMessage('План импорта изменился');
        $this->service->apply($rows, $report['fingerprint']);
    }

    public function test_existing_links_and_product_values_are_untouched(): void
    {
        $old = Attribute::create(['name' => 'Preserved '.$this->category->id, 'type' => 'text', 'is_filterable' => false]);
        $this->category->attributes()->attach($old->id, ['sort_order' => 37]);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create(['title' => 'Import preservation test', 'user_id' => $seller->id, 'category_id' => $this->category->id, 'price' => 10, 'stock' => 1, 'status' => 'draft']);
        DB::table('attribute_values')->insert(['product_id' => $product->id, 'attribute_id' => $old->id, 'value' => 'Preserve me', 'created_at' => now(), 'updated_at' => now()]);
        $links = DB::table('attribute_category')->orderBy('id')->get()->toArray();
        $definitions = Attribute::orderBy('id')->get()->toArray();
        $values = DB::table('attribute_values')->orderBy('id')->get()->toArray();
        $this->applyRows([2 => $this->row()]);
        self::assertEquals($values, DB::table('attribute_values')->orderBy('id')->get()->toArray());
        self::assertEquals($links, DB::table('attribute_category')->whereIn('id', array_column($links, 'id'))->orderBy('id')->get()->toArray());
        self::assertEquals($definitions, Attribute::whereIn('id', array_column($definitions, 'id'))->orderBy('id')->get()->toArray());
    }

    public function test_same_new_definition_is_shared_by_two_categories_and_groups_are_report_only(): void
    {
        $other = Category::factory()->create();
        $before = Attribute::count();
        $rows = [2 => $this->row(), 3 => $this->row(['category_slug' => $other->slug, 'group_name' => 'Другая группа'])];
        $report = $this->applyRows($rows);
        self::assertSame(1, $report['new_definitions']);
        self::assertSame($before + 1, Attribute::count());
        self::assertSame($this->category->attributes()->first()->id, $other->attributes()->first()->id);
        self::assertSame('Другая группа', $report['rows'][1]['raw']['group_name']);
    }

    public function test_non_leaf_category_is_rejected(): void
    {
        Category::factory()->create(['parent_id' => $this->category->id]);
        self::assertSame('INVALID', $this->service->preview([2 => $this->row()])['rows'][0]['status']);
    }

    public function test_failure_after_inserts_rolls_back_entire_batch(): void
    {
        $rows = [2 => $this->row(), 3 => $this->row(['attribute_name' => 'Second '.$this->category->id])];
        $report = $this->service->preview($rows);
        $before = Attribute::count();
        $inserts = 0;
        DB::listen(function ($query) use (&$inserts) {
            if (str_starts_with($query->sql, 'insert into `attribute_category`') && ++$inserts === 2) {
                throw new \RuntimeException('Injected failure');
            }
        });
        try {
            $this->service->apply($rows, $report['fingerprint']);
            self::fail('Expected failure');
        } catch (\RuntimeException $e) {
            self::assertSame('Injected failure', $e->getMessage());
        }
        self::assertSame($before, Attribute::count());
        self::assertSame(0, $this->category->attributes()->count());
    }

    public function test_cache_invalidation_is_registered_after_commit_for_category_and_parent(): void
    {
        $parent = Category::factory()->create();
        $this->category->update(['parent_id' => $parent->id]);
        Cache::put('cat.filters.'.$this->category->id, 'before');
        Cache::put('cat.filters.'.$parent->id, 'before');
        $callback = null;
        $manager = DB::getFacadeRoot();
        $proxy = \Mockery::mock($manager);
        $proxy->shouldReceive('afterCommit')->once()->andReturnUsing(function ($cb) use (&$callback) {
            $callback = $cb;
        });
        DB::swap($proxy);
        try {
            $this->applyRows([2 => $this->row()]);
        } finally {
            DB::swap($manager);
        }
        self::assertSame('before', Cache::get('cat.filters.'.$this->category->id));
        self::assertNotNull($callback);
        $callback(); // Simulate commit callback; the outer fixture transaction stays open.
        self::assertNull(Cache::get('cat.filters.'.$this->category->id));
        self::assertNull(Cache::get('cat.filters.'.$parent->id));
    }

    public function test_non_admin_cannot_access_any_import_action(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'buyer']));
        $this->get(route('admin.attribute-import.index'))->assertForbidden();
        $this->get(route('admin.attribute-import.template'))->assertForbidden();
        $this->post(route('admin.attribute-import.preview'))->assertForbidden();
        $this->post(route('admin.attribute-import.apply'))->assertForbidden();
    }

    private function upload(array $row): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import-http-');
        try {
            AttributeImportWorkbook::write($path, [$row]);

            return UploadedFile::fake()->createWithContent('attributes.xlsx', file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    public function test_admin_preview_and_confirmation_validate_file_and_token(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->post(route('admin.attribute-import.preview'), ['file' => $this->upload($this->row())]);
        $response->assertOk()->assertViewHas('token');
        $token = $response->viewData('token');
        $changed = $this->post(route('admin.attribute-import.apply'), ['file' => $this->upload($this->row(['unit' => 'cm'])), 'token' => $token, 'confirmed' => '1']);
        $changed->assertViewHas('failure');
        self::assertSame(0, $this->category->attributes()->count());
        $proof = json_decode(Crypt::decryptString($token), true);
        $proof['expires'] = time() - 1;
        $expired = Crypt::encryptString(json_encode($proof));
        $this->post(route('admin.attribute-import.apply'), ['file' => $this->upload($this->row()), 'token' => $expired, 'confirmed' => '1'])->assertViewHas('failure');
        $this->post(route('admin.attribute-import.apply'), ['file' => $this->upload($this->row()), 'token' => 'tampered', 'confirmed' => '1'])->assertViewHas('failure');
    }

    public function test_http_confirmation_applies_only_reviewed_plan_and_rejects_replay(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $upload = $this->upload($this->row());
        $response = $this->post(route('admin.attribute-import.preview'), ['file' => $upload]);
        $token = $response->viewData('token');
        $this->post(route('admin.attribute-import.apply'), ['file' => $upload, 'token' => $token, 'confirmed' => '1'])->assertViewHas('applied', true);
        self::assertSame(1, $this->category->attributes()->count());
        $this->post(route('admin.attribute-import.apply'), ['file' => $upload, 'token' => $token, 'confirmed' => '1'])->assertViewHas('failure');
        self::assertSame(1, $this->category->attributes()->count());
    }

    public function test_confirmation_is_bound_to_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $upload = $this->upload($this->row());
        $token = $this->post(route('admin.attribute-import.preview'), ['file' => $upload])->viewData('token');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post(route('admin.attribute-import.apply'), ['file' => $upload, 'token' => $token, 'confirmed' => '1'])->assertViewHas('failure');
        self::assertSame(0, $this->category->attributes()->count());
    }

    public function test_csrf_is_required_when_test_bypass_is_disabled(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        // Only disable Laravel's testing shortcut; retain the verified test connection.
        $this->app->instance('env', 'production');
        try {
            $this->post(route('admin.attribute-import.preview'))->assertStatus(419);
            $this->post(route('admin.attribute-import.apply'))->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        self::assertSame(0, $this->category->attributes()->count());
    }
}
