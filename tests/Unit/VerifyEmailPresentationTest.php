<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use PHPUnit\Framework\TestCase;

/** Presentation and signed-link checks, without booting Laravel or accessing a database. */
class VerifyEmailPresentationTest extends TestCase
{
    private Container $previousContainer;
    private mixed $previousFacadeApplication;
    private UrlGenerator $urls;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('config', new Repository([
            'app' => ['url' => 'http://127.0.0.1:8000'],
            'auth' => ['verification' => ['expire' => 60]],
        ]));
        $router = new Router(new Dispatcher($app), $app);
        $app->instance('router', $router);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
        require dirname(__DIR__, 2).'/routes/auth.php';
        $router->getRoutes()->refreshNameLookups();
        $this->urls = new UrlGenerator($router->getRoutes(), Request::create('http://127.0.0.1:8000'));
        // Ephemeral test key: preview links cannot authenticate against a real application.
        $key = bin2hex(random_bytes(32));
        $this->urls->setKeyResolver(fn () => $key);
        $app->instance('url', $this->urls);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    private function render(string $name, string $role = 'buyer'): array
    {
        $user = new User;
        $user->forceFill(['id' => 0, 'name' => $name, 'email' => 'preview@example.test', 'role' => $role]);
        $mail = (new VerifyEmail)->toMail($user);
        self::assertSame('emails.verify-email', $mail->view);
        $blade = new BladeCompiler(new Filesystem, sys_get_temp_dir());
        $compiled = $blade->compileString(file_get_contents(
            dirname(__DIR__, 2).'/resources/views/emails/verify-email.blade.php',
        ));
        $html = (static function (string $compiled, array $data): string {
            $__env = new \Illuminate\View\Factory(
                new \Illuminate\View\Engines\EngineResolver,
                new \Illuminate\View\FileViewFinder(new Filesystem, []),
                new Dispatcher,
            );
            extract($data, EXTR_SKIP);
            ob_start();
            try {
                eval('?>'.$compiled);
                return ob_get_contents();
            } finally {
                ob_end_clean();
            }
        })($compiled, $mail->viewData);

        return [$html, $mail->viewData['url']];
    }

    public function test_button_and_fallback_keep_the_same_valid_laravel_signed_url(): void
    {
        [$html, $url] = $this->render('Предпросмотр письма');
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $verificationLinks = [];
        foreach ($document->getElementsByTagName('a') as $link) {
            if (str_contains($link->getAttribute('href'), '/verify-email/')) {
                $verificationLinks[] = $link->getAttribute('href');
            }
        }
        self::assertSame([$url, $url], $verificationLinks);
        self::assertTrue($this->urls->hasValidSignature(Request::create($url)));
        self::assertFalse($this->urls->hasValidSignature(Request::create($url.'&tampered=1')));
        self::assertStringNotContainsString('max-width:1160px', $html);
        self::assertStringContainsString('max-width:680px', $html);
        self::assertStringNotContainsString('<script', $html);
        foreach (['svg', 'script', 'link', 'iframe', 'object', 'input', 'button'] as $tag) {
            self::assertSame(0, $document->getElementsByTagName($tag)->length, $tag);
        }
        self::assertDoesNotMatchRegularExpression('/(?:url\s*\(|@import|data:image|base64)/i', $html);
        self::assertSame(1, $document->getElementsByTagName('img')->length);
        $illustration = $document->getElementsByTagName('img')->item(0);
        self::assertSame('http://127.0.0.1:8000/images/email/verify-email-hero.png', $illustration->getAttribute('src'));
        self::assertNotSame('', $illustration->getAttribute('alt'));
        if ($preview = getenv('WV_EMAIL_PRESENTATION_PREVIEW')) {
            // Only the offline preview uses a local asset; actual email uses the site URL.
            $localImage = 'file:///'.str_replace('\\', '/', dirname(__DIR__, 2)).'/public/images/email/verify-email-hero.png';
            file_put_contents($preview, str_replace($illustration->getAttribute('src'), $localImage, $html));
        }
    }

    public function test_real_name_is_escaped_and_seller_requirements_are_not_overpromised(): void
    {
        [$html] = $this->render('<script>alert(1)</script>', 'seller');
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('также потребуется подтвердить личный телефон', $html);
        [$buyerHtml] = $this->render('Покупатель');
        self::assertStringNotContainsString('также потребуется подтвердить личный телефон', $buyerHtml);
    }
}
