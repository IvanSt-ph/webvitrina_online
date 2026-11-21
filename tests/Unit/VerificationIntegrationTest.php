<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyTelegramWebhookSecret;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/** Loads route definitions only: no kernel, dotenv, database, or external requests. */
class VerificationIntegrationTest extends TestCase
{
    private Router $router;
    private Container $previousContainer;
    private mixed $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('env', 'production');
        $this->router = new Router(new Dispatcher($app), $app);
        $app->instance('router', $this->router);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
        $this->router->group(['middleware' => 'web'], function (): void {
            require dirname(__DIR__, 2).'/routes/web.php';
        });
        require dirname(__DIR__, 2).'/routes/telegram.php';
        $this->router->getRoutes()->refreshNameLookups();
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public static function verificationRoutes(): array
    {
        return [
            ['verification.verify', 6, 1],
            ['verification.send', 6, 1],
            ['phone.telegram.store', 3, 10],
            ['phone.telegram.finalize', 6, 1],
            ['phone.telegram.status', 30, 1],
        ];
    }

    private function throttleArguments(string $name): array
    {
        $route = $this->router->getRoutes()->getByName($name);
        self::assertNotNull($route);
        $entries = array_values(array_filter($route->middleware(),
            fn ($entry) => str_starts_with($entry, 'throttle:')));
        self::assertCount(1, $entries);

        return explode(',', substr($entries[0], strlen('throttle:')));
    }

    private function requestForUser(int $id): Request
    {
        $request = Request::create('https://example.test/verification');
        $request->setUserResolver(fn () => new class($id) {
            public function __construct(private int $id) {}
            public function getAuthIdentifier(): int { return $this->id; }
        });

        return $request;
    }

    #[DataProvider('verificationRoutes')]
    public function test_exhausted_route_does_not_block_other_verification_actions(
        string $name, int $limit, int $minutes,
    ): void {
        $arguments = $this->throttleArguments($name);
        self::assertSame((string) $limit, $arguments[0]);
        self::assertSame((string) $minutes, $arguments[1]);
        $middleware = new ThrottleRequests(new RateLimiter(new Repository(new ArrayStore)));
        $request = $this->requestForUser(123);
        $next = fn () => new Response('ok');
        for ($i = 0; $i < $limit; $i++) {
            self::assertSame(200, $middleware->handle($request, $next, ...$arguments)->getStatusCode());
        }
        try {
            $middleware->handle($request, $next, ...$arguments);
            self::fail('The exhausted route must still be limited.');
        } catch (ThrottleRequestsException $exception) {
            self::assertSame(429, $exception->getStatusCode());
            self::assertGreaterThan(0, $exception->getHeaders()['Retry-After']);
        }
        foreach (self::verificationRoutes() as [$other]) {
            if ($other !== $name) {
                self::assertSame(200, $middleware->handle(
                    $request, $next, ...$this->throttleArguments($other),
                )->getStatusCode(), $other);
            }
        }
        self::assertSame(200, $middleware->handle(
            $this->requestForUser(456), $next, ...$arguments,
        )->getStatusCode());
    }

    public function test_route_security_contracts_are_preserved(): void
    {
        $routes = $this->router->getRoutes();
        foreach (self::verificationRoutes() as [$name]) {
            self::assertContains('auth', $routes->getByName($name)->middleware());
            self::assertContains('web', $routes->getByName($name)->middleware());
        }
        self::assertContains('signed', $routes->getByName('verification.verify')->middleware());
        self::assertContains('verified', $routes->getByName('phone.telegram.finalize')->middleware());
        foreach (['phone.telegram.store', 'phone.telegram.finalize', 'logout'] as $name) {
            self::assertSame(['POST'], $routes->getByName($name)->methods());
            self::assertSame(10, $routes->getByName($name)->locksFor());
        }
        $webhook = $routes->getByName('telegram.webhook');
        self::assertSame(['POST'], $webhook->methods());
        self::assertContains(VerifyTelegramWebhookSecret::class, $webhook->middleware());
        self::assertNotContains('web', $webhook->middleware());
    }

    public function test_csp_allows_telegram_forms_without_widening_other_directives(): void
    {
        $response = (new SecurityHeaders)->handle(Request::create('https://example.test'),
            fn () => new Response('ok'));
        $directives = explode('; ', $response->headers->get('Content-Security-Policy'));
        self::assertContains("form-action 'self' https://t.me", $directives);
        self::assertContains("default-src 'self'", $directives);
        self::assertContains("frame-ancestors 'self'", $directives);
        self::assertContains("object-src 'none'", $directives);
        self::assertContains('upgrade-insecure-requests', $directives);
    }
}
