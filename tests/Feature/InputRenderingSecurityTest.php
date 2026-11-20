<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Notifications\MarketplaceEventNotification;
use App\Support\SafeRedirect;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InputRenderingSecurityTest extends TestCase
{
    public static function textInputs(): array
    {
        return array_map(fn ($text) => [$text], [
            'PRD-12345',
            'Иван & Олег <товар> "кавычки" \' и \\',
            "'+(globalThis.auditMarker=1)+'",
            '</script><svg onload="globalThis.auditMarker=1"></svg>',
            '[AUDIT-LINK](/audit-marker) ![IMAGE](/audit-image)',
            "**bold** _italic_ `code` ~~strike~~\n\n# heading\n> quote",
            '&#91;link&#93;(/audit) &lt;img&gt;',
            '    indented text',
            "\tindented text",
        ]);
    }

    #[DataProvider('textInputs')]
    public function test_sku_is_copied_as_exact_text_without_executing_it(string $sku): void
    {
        $product = new Product(['sku' => $sku]);
        $product->id = 123;
        $html = view('components.product.favorite', ['product' => $product, 'isFav' => false])->render();
        preg_match('/@click="([^"]*navigator\.clipboard[^"]*)"/', $html, $matches);
        self::assertNotEmpty($matches);
        $expression = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $result = $this->javascript($expression, <<<'JS'
            const context = { copied: false, navigator: {clipboard: {writeText: text => context.text = text}},
                setTimeout: (callback, delay) => { context.callback = callback; context.delay = delay; } };
            vm.createContext(context);
            vm.runInContext(input, context);
            const copied = context.copied;
            context.callback();
            console.log(JSON.stringify({text: context.text, copied, reset: context.copied,
                delay: context.delay, executed: 'auditMarker' in context}));
            JS);
        self::assertSame($sku, $result['text']);
        self::assertTrue($result['copied']);
        self::assertFalse($result['reset']);
        self::assertSame(1500, $result['delay']);
        self::assertFalse($result['executed']);
    }

    #[DataProvider('textInputs')]
    public function test_notification_preserves_names_as_text_in_html_and_plain_mail(string $name): void
    {
        $body = $name . ' написал(а) в чате.';
        $message = (new MarketplaceEventNotification('Новое сообщение', $body))
            ->toMail((object) ['name' => $name]);
        $html = (string) $message->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXPath($dom);
        $cell = $xpath->query('//td[contains(@class,"content-cell")]')->item(0);
        self::assertNotNull($cell);
        self::assertStringContainsString($body, $cell->textContent);
        self::assertStringContainsString('Здравствуйте, ' . $name . '!', $cell->textContent);
        self::assertSame(0, $xpath->query('.//a | .//img | .//svg | .//script | .//strong | .//em | .//code | .//blockquote', $cell)->length);
        self::assertSame(1, $xpath->query('.//h1', $cell)->length);
        $plain = (string) app(Markdown::class)->renderText($message->markdown, $message->data());
        self::assertStringContainsString($name, $plain);
    }

    public function test_notification_keeps_its_real_action_link(): void
    {
        $message = (new MarketplaceEventNotification('Новое сообщение', '[fake](/audit)', '/my-chats/1'))
            ->toMail((object) ['name' => '[fake](/audit)']);
        $html = (string) $message->render();
        self::assertStringContainsString('href="' . url('/my-chats/1') . '"', $html);
        self::assertStringNotContainsString('href="/audit"', $html);
    }

    public static function redirects(): array
    {
        return [
            ['/orders/1?tab=items#details', '/orders/1?tab=items#details'],
            ['/search?q=%D0%A2%D0%B5%D1%81%D1%82', '/search?q=%D0%A2%D0%B5%D1%81%D1%82'],
            ['https://webvitrina.test/orders/1', '/orders/1'],
            ['https://webvitrina.test', '/'],
            ['//outside.invalid', null], ['/\\outside.invalid', null],
            ['/%5coutside.invalid', null], ['/%2foutside.invalid', null],
            ["/\t/outside.invalid", null], ["\n/orders", null],
            ['https://webvitrina.test//outside.invalid', null],
            ['https://webvitrina.test/%2foutside.invalid', null],
            ['https://webvitrina.test@outside.invalid/', null],
            ['https://user@webvitrina.test/', null],
            ['https://webvitrina.test:444/', null],
            ['javascript:void(0)', null], ['data:text/html,test', null],
        ];
    }

    #[DataProvider('redirects')]
    public function test_redirects_stay_on_the_configured_origin(string $input, ?string $expected): void
    {
        config(['app.url' => 'https://webvitrina.test']);
        self::assertSame($expected, SafeRedirect::internalPath($input));
    }

    public function test_admin_old_values_remain_data_in_alpine_and_map_scripts(): void
    {
        $payload = "'+(globalThis.auditMarker=1)+'";
        request()->setLaravelSession(session()->driver());
        session()->put('_old_input', array_fill_keys(['sku', 'country_id', 'city_id', 'latitude', 'longitude'], $payload));
        $create = file_get_contents(resource_path('views/admin/products/create.blade.php'));
        preg_match('/sku: .*?,\R/', $create, $sku);
        preg_match('/x-init="(init\(@js[^\r\n]+)"/', $create, $init);
        foreach (['({' . trim($sku[0]) . '})', $init[1]] as $template) {
            $expression = html_entity_decode(Blade::render($template), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $result = $this->javascript($expression, <<<'JS'
                const context = {init: (...args) => context.args = args};
                vm.createContext(context);
                const value = vm.runInContext(input, context);
                console.log(JSON.stringify({value: value?.sku ?? context.args[0], executed: 'auditMarker' in context}));
                JS);
            self::assertSame($payload, $result['value']);
            self::assertFalse($result['executed']);
        }
        foreach (['create', 'edit'] as $view) {
            $source = file_get_contents(resource_path("views/admin/products/{$view}.blade.php"));
            preg_match('/    let lat = .*?    if \(!Number\.isFinite\(lng\)\).*?;/s', $source, $map);
            self::assertNotEmpty($map);
            $script = Blade::render($map[0]);
            $result = $this->javascript($script, <<<'JS'
                const context = {productLat: 47.0105, productLng: 28.8638};
                vm.createContext(context);
                const value = vm.runInContext(input + ';({lat,lng})', context);
                console.log(JSON.stringify({...value, executed: 'auditMarker' in context}));
                JS);
            self::assertSame(47.0105, $result['lat']);
            self::assertSame(28.8638, $result['lng']);
            self::assertFalse($result['executed']);
        }
    }

    private function javascript(string $input, string $test): array
    {
        $process = new Process(['node', '-e', 'const vm=require("node:vm");let data="";process.stdin.on("data",c=>data+=c);process.stdin.on("end",()=>{const input=JSON.parse(data);' . $test . '});']);
        $process->setInput(json_encode($input, JSON_THROW_ON_ERROR));
        $process->setWorkingDirectory(storage_path('framework/testing'));
        $process->setTimeout(15);
        try {
            $process->mustRun();

            return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            $process->stop(0);
        }
    }
}
