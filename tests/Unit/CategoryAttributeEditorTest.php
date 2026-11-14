<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Js;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CategoryAttributeEditorTest extends TestCase
{
    public static function types(): array
    {
        return array_map(fn ($type) => [$type], ['text', 'select', 'number', 'color']);
    }

    #[DataProvider('types')]
    public function test_rendered_edit_handler_preserves_complete_payload(string $type): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/views/admin/categories/attributes.blade.php');
        self::assertSame(1, preg_match('/<button\b[^>]*@click=[\s\S]*?title="Редактировать">/', $source, $match));
        $attr = (object) [
            'id' => 263, 'name' => 'Цвет "корпуса" O\'Brien <test> &',
            'type' => $type, 'unit' => 'см', 'is_filterable' => true,
            'options' => $type === 'select' ? ['Один', 'Два "2"'] : [],
            'colors' => collect($type === 'color' ? array_map(fn ($id) => ['id' => $id], range(1, 15)) : []),
        ];
        $compiler = new BladeCompiler(new Filesystem, sys_get_temp_dir());
        $compiled = $compiler->compileString($match[0].'</button>');
        ob_start();
        try {
            eval('?>'.$compiled);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $dom = \Dom\HTMLDocument::createFromString($html, overrideEncoding: 'UTF-8');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $expected = ['id' => $attr->id, 'name' => $attr->name, 'type' => $type,
            'unit' => $attr->unit, 'is_filterable' => true,
            'options' => implode("\n", $attr->options), 'colors' => $attr->colors->pluck('id')->values()];
        self::assertSame('openEditor('.Js::from($expected)->toHtml().')', $dom->getElementsByTagName('button')->item(0)->getAttribute('@click'));
    }
}
