<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\PortalLocale;
use App\Http\Middleware\PublicLocale;
use App\Http\Middleware\SecurityHeaders;
use App\Services\TranslationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => '/admin/login');
        $middleware->append(PortalLocale::class);
        $middleware->append([SecurityHeaders::class, PublicLocale::class]);
        $middleware->alias(['active-user' => EnsureActiveUser::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*', 'sanctum/*') || $request->expectsJson());
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*', 'sanctum/*') && ! $request->expectsJson()) {
                return null;
            }
            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                $exception instanceof QueryException && in_array((string) $exception->getCode(), ['23000', '23505', '23503'], true) => 409,
                default => 500,
            };
            $message = match ($status) {
                401 => 'برای ادامه وارد حساب شوید.',
                403 => 'اجازه انجام این عملیات را ندارید.',
                404 => 'موردی یافت نشد.',
                405 => 'روش درخواست مجاز نیست.',
                409 => 'اطلاعات با وضعیت فعلی تعارض دارد.',
                419 => 'نشست منقضی شده است. دوباره وارد شوید.',
                422 => 'اطلاعات ارسال‌شده معتبر نیست.',
                429 => 'تعداد درخواست‌ها بیش از حد مجاز است.',
                default => 'خطایی رخ داد.',
            };
            if (TranslationService::locale() === 'en') {
                $message = match ($status) {
                    401 => 'Please sign in to continue.', 403 => 'You do not have permission to perform this action.',
                    404 => 'The requested item was not found.', 405 => 'This request method is not allowed.',
                    409 => 'The submitted information conflicts with the current state.', 419 => 'Your session expired. Please sign in again.',
                    422 => 'The submitted information is invalid.', 429 => 'Too many requests. Please try again shortly.',
                    default => 'An error occurred.',
                };
            }
            $payload = ['success' => false, 'message' => $message];
            if ($exception instanceof ValidationException) {
                $payload['errors'] = $exception->errors();
            }
            $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

            return response()->json($payload, $status, $headers);
        });
    })->create();
