<?php

namespace Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Tests\Support\IsolatedSecurityHeaders;

class IsolatedSecurityHeadersTest extends TestCase
{
    public static function environments(): array
    {
        return [['local', 'http://localhost:5173'], ['production', 'upgrade-insecure-requests']];
    }

    #[DataProvider('environments')]
    public function test_real_headers_preserve_the_original_application(string $environment, string $directive): void
    {
        $previous = Container::getInstance();
        try {
            $application = new Application();
            $application->detectEnvironment(static fn () => 'testing');
            $response = new Response('original body', 201);
            $this->assertSame($response, IsolatedSecurityHeaders::apply($environment, $response));
            $this->assertSame($application, Container::getInstance());
            $this->assertSame('testing', $application->environment());
            $this->assertSame(201, $response->getStatusCode());
            $this->assertSame('original body', $response->getContent());
            $this->assertStringContainsString($directive, $response->headers->get('Content-Security-Policy'));
        } finally {
            Container::setInstance($previous);
        }
    }

    public function test_container_is_restored_when_header_writing_throws(): void
    {
        $original = Container::getInstance();
        $failure = new \RuntimeException('Injected header failure');
        $headers = $this->createMock(ResponseHeaderBag::class);
        $headers->expects($this->once())->method('set')->willThrowException($failure);
        $response = new Response();
        $response->headers = $headers;
        try {
            IsolatedSecurityHeaders::apply('production', $response);
            $this->fail('Expected the original header exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        } finally {
            $this->assertSame($original, Container::getInstance());
        }
    }
}
