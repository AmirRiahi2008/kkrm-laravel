<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PortalLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*', 'sanctum/*')) {
            $locale = $request->is('en', 'en/*') ? 'en' : 'fa';
            $request->headers->set('X-Site-Locale', $locale);
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
