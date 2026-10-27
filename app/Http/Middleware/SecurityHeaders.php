<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->is('api/*', 'sanctum/*', 'admin', 'admin/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request));
        $response->headers->set('X-Request-ID', (string) Str::uuid());
        if ($request->is('api/v1/admin/*', 'api/v1/auth/*') || $response->getStatusCode() >= 400) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request): string
    {
        if ($request->is('api/*', 'sanctum/*')) {
            return "default-src 'none'; frame-ancestors 'none'; base-uri 'none'";
        }

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'https:', 'data:', 'blob:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'frame-src' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ];

        $devServer = app()->isLocal() ? Vite::devServerUrl() : null;

        if (is_string($devServer) && preg_match('~\Ahttps?://(?:[a-z0-9.-]+|\[[a-f0-9:]+\])(?::[0-9]{1,5})?/?\z~iD', $devServer)) {
            $origin = rtrim($devServer, '/');

            foreach (['script-src', 'style-src', 'img-src', 'font-src', 'connect-src'] as $directive) {
                $directives[$directive][] = $origin;
            }

            $directives['connect-src'][] = preg_replace('~^http~', 'ws', $origin);
        }

        return collect($directives)
            ->map(fn (array $sources, string $directive): string => $directive.' '.implode(' ', $sources))
            ->implode('; ');
    }
}
