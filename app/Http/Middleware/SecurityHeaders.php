<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add browser security headers without breaking the current Blade/Vite setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $styleSources = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net', 'https://cdn.jsdelivr.net', 'https://unpkg.com'];
        $fontSources = ["'self'", 'data:', 'https://fonts.bunny.net', 'https://cdn.jsdelivr.net'];
        // Alpine's standard build evaluates directive expressions at runtime.
        // Until the app moves to Alpine's CSP-compatible build, unsafe-eval is
        // required for x-data / x-show directives to keep working correctly.
        $scriptSources = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://unpkg.com'];
        $connectSources = ["'self'", 'https://nominatim.openstreetmap.org'];

        if (app()->environment('local')) {
            $styleSources[] = 'http://127.0.0.1:5173';
            $styleSources[] = 'http://localhost:5173';
            $scriptSources[] = 'http://127.0.0.1:5173';
            $scriptSources[] = 'http://localhost:5173';
            $connectSources[] = 'ws://127.0.0.1:5173';
            $connectSources[] = 'ws://localhost:5173';
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "frame-ancestors 'self'",
                "form-action 'self'",
                "img-src 'self' data: https:",
                'font-src ' . implode(' ', $fontSources),
                'style-src ' . implode(' ', $styleSources),
                'script-src ' . implode(' ', $scriptSources),
                'connect-src ' . implode(' ', $connectSources),
            ])
        );

        return $response;
    }
}
