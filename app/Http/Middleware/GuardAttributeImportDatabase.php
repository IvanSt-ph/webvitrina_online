<?php

namespace App\Http\Middleware;

use App\Services\AttributeImport\ImportDatabaseGuard;
use App\Services\AttributeImport\ImportException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class GuardAttributeImportDatabase
{
    public function __construct(private readonly ImportDatabaseGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('admin.attribute-import.preview', 'admin.attribute-import.apply')) {
            try {
                $this->guard->assertAllowed();
            } catch (ImportException $exception) {
                // No Blade/session rendering: stop before session/throttle/cache writes.
                return new Response($exception->getMessage(), 503, [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Cache-Control' => 'no-store',
                ]);
            }
        }

        return $next($request);
    }
}
