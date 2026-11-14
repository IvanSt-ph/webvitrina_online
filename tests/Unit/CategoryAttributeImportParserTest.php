<?php

namespace Tests\Unit;

use App\Services\AttributeImport\XlsxReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\AttributeImportWorkbook;

final class CategoryAttributeImportParserTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'xlsx-test-');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
    }

    public function test_template_reads_values_and_excel_line_numbers(): void
    {
        AttributeImportWorkbook::write($this->path, [['slug', 'Группа', 'Размер', 'select', '', '1', '["S","M"]', '0']]);
        $rows = (new XlsxReader)->read($this->path);
        self::assertSame('Размер', $rows[2]['attribute_name']);
        self::assertSame('["S","M"]', $rows[2]['options']);
    }

    public function test_approved_color_workbook_is_read_without_modification(): void
    {
        $path = dirname(__DIR__, 2).'/docs/templates/category-attributes-colors-2026-11-13.xlsx';
        $hash = hash_file('sha256', $path);
        $rows = (new XlsxReader)->read($path);
        self::assertCount(2101, $rows);
        self::assertSame([9], array_values(array_unique(array_map('count', $rows))));
        $colors = array_filter($rows, fn ($row) => $row['attribute_name'] === 'Цвет');
        self::assertCount(78, $colors);
        self::assertSame(['color'], array_values(array_unique(array_column($colors, 'type'))));
        self::assertSame(['1'], array_values(array_unique(array_column($colors, 'is_filterable'))));
        foreach ($colors as $row) {
            self::assertSame(\Tests\Support\ApprovedColorPalette::NAMES, json_decode($row['colors'], true, 16, JSON_THROW_ON_ERROR));
        }
        self::assertSame($hash, hash_file('sha256', $path));
    }

    public function test_new_format_reads_palette_and_old_format_supplies_empty_palette(): void
    {
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Цвет', 'color', '', '1', '', '3', '["Синий"]']], [], true);
        self::assertSame('["Синий"]', (new XlsxReader)->read($this->path)[2]['colors']);
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Размер', 'text', '', '0', '', '0']]);
        self::assertSame('', (new XlsxReader)->read($this->path)[2]['colors']);
    }

    public function test_old_headers_with_ninth_data_column_are_rejected(): void
    {
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Name', 'text', '', '0', '', '0', 'hidden']]);
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public function test_corrupt_file_is_rejected(): void
    {
        file_put_contents($this->path, 'not a zip');
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    #[DataProvider('relationshipPaths')]
    public function test_internal_absolute_and_relative_relationships(string $root, string $sheet): void
    {
        $this->relationshipFixture($root, $sheet);
        self::assertSame('Name', (new XlsxReader)->read($this->path)[2]['attribute_name']);
    }

    public static function relationshipPaths(): array
    {
        return [
            ['xl/workbook.xml', 'worksheets/sheet1.xml'],
            ['/xl/workbook.xml', '/xl/worksheets/sheet1.xml'],
            ['xl/workbook.xml', '/xl/worksheets/sheet1.xml'],
            ['/xl/workbook.xml', 'worksheets/sheet1.xml'],
        ];
    }

    private function relationshipFixture(string $root, string $sheet, string $mode = '', string $extra = ''): void
    {
        $ns = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Name', 'text', '', '0', '', '0']], [
            '_rels/.rels' => '<Relationships xmlns="'.$ns.'"><Relationship Id="root" Type="'.$office.'officeDocument" Target="'.htmlspecialchars($root, ENT_XML1 | ENT_QUOTES).'"/></Relationships>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="'.$ns.'"><Relationship Id="rId1" Type="'.$office.'worksheet" Target="'.htmlspecialchars($sheet, ENT_XML1 | ENT_QUOTES).'"'.$mode.'/>'.$extra.'</Relationships>',
        ]);
    }

    #[DataProvider('invalidTargets')]
    public function test_unsafe_relationship_paths_are_rejected(string $target): void
    {
        $this->relationshipFixture('xl/workbook.xml', $target);
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public static function invalidTargets(): array
    {
        return array_map(fn ($value) => [$value], [
            'https://example.com/sheet.xml', 'file:///xl/worksheets/sheet1.xml', '//xl/worksheets/sheet1.xml',
            '../xl/worksheets/sheet1.xml', 'worksheets/../worksheets/sheet1.xml', './worksheets/sheet1.xml',
            '/xl//worksheets/sheet1.xml', 'worksheets\\sheet1.xml', '%2Fxl/worksheets/sheet1.xml',
            '/xl/%2e%2e/worksheets/sheet1.xml', 'worksheets/sheet1.xml?x=1', 'worksheets/sheet1.xml#x',
            ' worksheets/sheet1.xml', 'worksheets/sheet1.xml ', 'worksheets/sheet2.xml', '',
            'xl/worksheets/sheet1.xml', '/worksheets/sheet1.xml',
        ]);
    }

    public function test_explicit_internal_mode_is_allowed_but_external_mode_is_not(): void
    {
        $this->relationshipFixture('/xl/workbook.xml', '/xl/worksheets/sheet1.xml', ' TargetMode="Internal"');
        self::assertCount(1, (new XlsxReader)->read($this->path));
        $this->relationshipFixture('/xl/workbook.xml', '/xl/worksheets/sheet1.xml', ' TargetMode="External"');
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    #[DataProvider('duplicateRelationships')]
    public function test_duplicate_ids_or_canonical_targets_are_rejected(string $id, string $target, string $type): void
    {
        $extra = '<Relationship Id="'.$id.'" Target="'.$target.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/'.$type.'"/>';
        $this->relationshipFixture('xl/workbook.xml', 'worksheets/sheet1.xml', '', $extra);
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public static function duplicateRelationships(): array
    {
        return [['rId2', '/xl/worksheets/sheet1.xml', 'worksheet'], ['rId1', 'styles.xml', 'styles']];
    }

    public function test_missing_relationship_target_is_rejected(): void
    {
        $extra = '<Relationship Id="rId2" Target="styles.xml" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"/>';
        $this->relationshipFixture('xl/workbook.xml', 'worksheets/sheet1.xml', '', $extra);
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public function test_wrong_relationship_type_is_rejected(): void
    {
        $this->relationshipFixture('xl/workbook.xml', 'worksheets/sheet1.xml');
        $zip = new \ZipArchive;
        $zip->open($this->path);
        $xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $zip->addFromString('xl/_rels/workbook.xml.rels', str_replace('/relationships/worksheet', '/relationships/hyperlink', $xml));
        $zip->close();
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public function test_plain_str_cells_are_read_but_formulas_remain_forbidden(): void
    {
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Name', 'text', '', '0', '', '0']]);
        $zip = new \ZipArchive;
        $zip->open($this->path);
        $sheet = str_replace('<c r="C2" t="inlineStr"><is><t>Name</t></is></c>', '<c r="C2" t="str"><v>Name</v></c>', $zip->getFromName('xl/worksheets/sheet1.xml'));
        $sheet = str_replace('<c r="E2" t="inlineStr"><is><t></t></is></c>', '<c r="E2" t="str"/>', $sheet);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        self::assertSame('Name', (new XlsxReader)->read($this->path)[2]['attribute_name']);
        self::assertSame('', (new XlsxReader)->read($this->path)[2]['unit']);
        $zip->open($this->path);
        $zip->addFromString('xl/worksheets/sheet1.xml', str_replace('<v>Name</v>', '<f>"Name"</f><v>Name</v>', $sheet));
        $zip->close();
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public function test_shared_strings_and_numeric_cells_are_read_without_coercion(): void
    {
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Name', 'text', '', '0', '', '12']]);
        $zip = new \ZipArchive;
        $zip->open($this->path);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sheet = str_replace('<c r="C2" t="inlineStr"><is><t>Name</t></is></c>', '<c r="C2" t="s"><v>0</v></c>', $sheet);
        $sheet = str_replace('<c r="H2" t="inlineStr"><is><t>12</t></is></c>', '<c r="H2" t="n"><v>12</v></c>', $sheet);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Название</t></si></sst>');
        $zip->close();
        $rows = (new XlsxReader)->read($this->path);
        self::assertSame('Название', $rows[2]['attribute_name']);
        self::assertSame('12', $rows[2]['sort_order']);
    }

    #[DataProvider('unsafeParts')]
    public function test_unsafe_or_unsupported_workbook_is_rejected(array $parts): void
    {
        AttributeImportWorkbook::write($this->path, [['slug', '', 'Name', 'text', '', '0', '', '0']], $parts);
        $this->expectException(\RuntimeException::class);
        (new XlsxReader)->read($this->path);
    }

    public static function unsafeParts(): array
    {
        $sheet = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1"><v>bad header</v></c></row></sheetData></worksheet>';

        return [
            'missing columns' => [['xl/worksheets/sheet1.xml' => $sheet]],
            'formula' => [['xl/worksheets/sheet1.xml' => str_replace('<v>', '<f>1+1</f><v>', $sheet)]],
            'entity' => [['xl/styles.xml' => '<!DOCTYPE x [<!ENTITY test SYSTEM "file:///etc/passwd">]><x>&test;</x>']],
            'external link' => [['xl/_rels/workbook.xml.rels' => '<Relationships><Relationship TargetMode="External" Target="https://example.com"/></Relationships>']],
            'traversal' => [['../outside.xml' => '<x/>']],
            'extra sheet' => [['xl/worksheets/sheet2.xml' => '<x/>']],
            'compression bomb' => [['xl/styles.xml' => '<x>'.str_repeat('x', 100000).'</x>']],
            'broken xml' => [['xl/styles.xml' => '<x>']],
            'non utf8 declaration' => [['xl/styles.xml' => '<?xml version="1.0" encoding="ISO-8859-1"?><x/>']],
            'oversized part' => [['xl/styles.xml' => '<x>'.str_repeat('x', 8388608).'</x>']],
        ];
    }
}
