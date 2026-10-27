<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($request->is('api/v1/admin/*', 'api/v1/auth/*') ? 'fa' : TranslationService::locale());

        return $next($request);
    }
}
