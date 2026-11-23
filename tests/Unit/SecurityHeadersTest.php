<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersTest extends TestCase
{
    public static function environments(): array
    {
        return [
            ['production'],
            ['local'],
            ['testing'],
            ['staging'],
        ];
    }

    #[DataProvider('environments')]
    public function test_self_hosted_assets_and_required_external_services_have_the_expected_policy(
        string $environment
    ): void {
        // Exercise middleware directly: no application boot, database or migrations.
        $previous = Container::getInstance();

        $app = new Application(dirname(__DIR__, 2));
        $app->instance('env', $environment);

        try {
            $response = (new SecurityHeaders)->handle(
                Request::create('http://127.0.0.1:8000/'),
                fn () => new Response('ok')
            );

            $policy = $response->headers->get('Content-Security-Policy');
            $directives = explode('; ', $policy);
            $viteHttp = $environment === 'local'
                ? ' http://127.0.0.1:5173 http://localhost:5173'
                : '';
            $viteWebSocket = $environment === 'local'
                ? ' ws://127.0.0.1:5173 ws://localhost:5173'
                : '';

            self::assertContains("default-src 'self'", $directives);
            self::assertContains("base-uri 'self'", $directives);
            self::assertContains("object-src 'none'", $directives);
            self::assertContains("frame-ancestors 'self'", $directives);
            self::assertContains("form-action 'self' https://t.me", $directives);
            self::assertContains("style-src 'self' 'unsafe-inline'{$viteHttp}", $directives);
            self::assertContains("script-src 'self' 'unsafe-inline' 'unsafe-eval'{$viteHttp}", $directives);
            self::assertContains("font-src 'self' data:{$viteHttp}", $directives);
            self::assertContains(
                "img-src 'self' data: blob: https://ui-avatars.com https://*.tile.openstreetmap.org{$viteHttp}",
                $directives
            );
            self::assertContains(
                "connect-src 'self' https://nominatim.openstreetmap.org{$viteHttp}{$viteWebSocket}",
                $directives
            );

            foreach ([
                'cdn.jsdelivr.net',
                'cdnjs.cloudflare.com',
                'unpkg.com',
                'fonts.bunny.net',
            ] as $host) {
                self::assertStringNotContainsString($host, $policy);
            }

            self::assertStringContainsString(
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                $policy
            );

            $localStyles = $environment === 'local'
                ? ' http://127.0.0.1:5173 http://localhost:5173'
                : '';

            self::assertStringContainsString(
                "style-src 'self' 'unsafe-inline'{$localStyles};",
                $policy
            );

            $localFonts = $environment === 'local'
                ? ' http://127.0.0.1:5173 http://localhost:5173'
                : '';

            self::assertStringContainsString(
                "font-src 'self' data:{$localFonts};",
                $policy
            );

            self::assertStringContainsString(
                "connect-src 'self' https://nominatim.openstreetmap.org",
                $policy
            );

            self::assertStringContainsString(
                'https://*.tile.openstreetmap.org',
                $policy
            );

            self::assertStringContainsString(
                "frame-src 'self' https://www.youtube.com",
                $policy
            );

            self::assertSame(
                'nosniff',
                $response->headers->get('X-Content-Type-Options')
            );

            if ($environment === 'local') {
                self::assertStringContainsString(
                    'http://127.0.0.1:5173',
                    $policy
                );

                self::assertStringContainsString(
                    'http://localhost:5173',
                    $policy
                );

                self::assertStringContainsString(
                    'ws://localhost:5173',
                    $policy
                );
            } else {
                self::assertStringNotContainsString(
                    'localhost:5173',
                    $policy
                );

                self::assertStringNotContainsString(
                    '127.0.0.1:5173',
                    $policy
                );
            }

            if ($environment === 'production') {
                self::assertStringContainsString(
                    'upgrade-insecure-requests',
                    $policy
                );

                self::assertSame(
                    'max-age=31536000; includeSubDomains',
                    $response->headers->get('Strict-Transport-Security')
                );
            } else {
                self::assertStringNotContainsString(
                    'upgrade-insecure-requests',
                    $policy
                );

                self::assertFalse(
                    $response->headers->has('Strict-Transport-Security')
                );
            }
        } finally {
            Container::setInstance($previous);
        }
    }
}
