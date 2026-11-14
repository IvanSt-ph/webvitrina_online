<?php

namespace Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;

final class SellerAttributeFieldsTest extends TestCase
{
    private function render(array $old = [], array $messages = [], ?string $saved = '2'): \Dom\HTMLDocument
    {
        $previous = Container::getInstance();
        $container = new Container;
        Container::setInstance($container);
        $session = new Store('test', new ArraySessionHandler(120));
        $session->put('_old_input', $old);
        $request = new Request;
        $request->setLaravelSession($session);
        $container->instance('request', $request);
        $colors = collect(\Tests\Support\ApprovedColorPalette::catalog())->map(fn ($c) => (object) $c);
        $attributes = collect([
            (object) ['id' => 263, 'name' => 'Цвет', 'type' => 'color', 'unit' => null, 'value' => $saved, 'colors' => $colors],
            (object) ['id' => 10, 'name' => 'Вес', 'type' => 'number', 'unit' => 'кг', 'value' => '0'],
            (object) ['id' => 11, 'name' => 'Материал', 'type' => 'text', 'unit' => null, 'value' => 'Хлопок'],
            (object) ['id' => 12, 'name' => 'Размер', 'type' => 'select', 'unit' => null, 'value' => 'M', 'options' => ['S', 'M']],
        ]);
        $errors = (new ViewErrorBag)->put('default', new MessageBag($messages));
        $compiler = new BladeCompiler(new Filesystem, sys_get_temp_dir());
        $compiled = $compiler->compileString(file_get_contents(__DIR__.'/../../resources/views/seller/products/partials/attributes.blade.php'));
        // Blade's foreach stack is in-memory only; no application or database boot.
        $__env = new class {
            use \Illuminate\View\Concerns\ManagesLoops;
        };
        ob_start();
        try {
            eval('?>'.$compiled);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
            Container::setInstance($previous);
        }
        return \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR, 'UTF-8');
    }

    public function test_edit_renders_all_colors_and_saved_id_without_changing_payload(): void
    {
        $doc = $this->render();
        self::assertCount(15, $doc->querySelectorAll('input[type="radio"]'));
        self::assertCount(1, $doc->querySelectorAll('input[type="radio"][checked]'));
        $selected = $doc->querySelector('input[type="radio"][checked]');
        self::assertSame('2', $selected->getAttribute('value'));
        self::assertSame('attributes[263]', $selected->getAttribute('name'));
        self::assertSame('number', $doc->querySelector('#seller-attribute-10')->getAttribute('type'));
        self::assertSame('0', $doc->querySelector('#seller-attribute-10')->getAttribute('value'));
        self::assertSame('Хлопок', $doc->querySelector('#seller-attribute-11')->getAttribute('value'));
        self::assertSame('M', $doc->querySelector('option[selected]')->getAttribute('value'));
    }

    public function test_validation_old_value_overrides_saved_color_and_exposes_error(): void
    {
        $doc = $this->render(['attributes' => [263 => '15']], ['attributes.263' => 'Проверьте цвет']);
        self::assertSame('15', $doc->querySelector('input[type="radio"][checked]')->getAttribute('value'));
        self::assertSame('true', $doc->querySelector('[data-color-field]')->getAttribute('aria-invalid'));
        self::assertSame('Проверьте цвет', $doc->querySelector('#seller-attribute-263-error')->textContent);
    }

    public function test_creation_does_not_select_a_color_automatically(): void
    {
        $doc = $this->render(saved: null);
        self::assertCount(0, $doc->querySelectorAll('input[type="radio"][checked]'));
        self::assertSame('Цвет не выбран', $doc->querySelector('[data-color-summary]')->textContent);
    }

    public function test_general_and_field_errors_are_both_visible(): void
    {
        $doc = $this->render(messages: ['attributes' => 'Характеристика не принадлежит категории.', 'attributes.263' => 'Проверьте цвет']);
        self::assertCount(2, $doc->querySelectorAll('[role="alert"]'));
        self::assertStringContainsString('Характеристика не принадлежит категории.', $doc->body->textContent);
        self::assertSame('Проверьте цвет', $doc->querySelector('#seller-attribute-263-error')->textContent);
    }

    public function test_color_payload_uses_hidden_empty_before_selected_radio_and_preserves_clear_after_error(): void
    {
        foreach ([['attributes' => [263 => '']], ['attributes' => [263 => null]], []] as $old) {
            $doc = $this->render(old: $old);
            $pairs = [];
            foreach ($doc->querySelectorAll('[data-color-field] input') as $input) {
                if ($input->getAttribute('type') === 'hidden' || $input->hasAttribute('checked')) {
                    $pairs[] = urlencode($input->getAttribute('name')).'='.urlencode($input->getAttribute('value'));
                }
            }
            parse_str(implode('&', $pairs), $payload);
            self::assertSame($old === [] ? '2' : '', $payload['attributes'][263]);
            self::assertSame($old !== [], $doc->querySelector('[data-color-reset]')->hasAttribute('hidden'));
        }
    }
}
