<?php

namespace Tests\Support;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Exercise the real header middleware without changing the database application's environment. */
final class IsolatedSecurityHeaders
{
    public static function apply(string $environment, Response $response): Response
    {
        if (! in_array($environment, ['local', 'production'], true)) {
            throw new \InvalidArgumentException('Unsupported header test environment.');
        }

        $original = Container::getInstance();
        try {
            // No kernel bootstrap, configuration files, sessions or database providers.
            $isolated = new Application();
            $isolated->detectEnvironment(static fn () => $environment);

            return (new SecurityHeaders())->handle(
                Request::create('/'), static fn () => $response,
            );
        } finally {
            Container::setInstance($original);
        }
    }
}
