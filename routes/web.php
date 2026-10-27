<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\Web\ManagementController;
use App\Http\Controllers\Web\PortalController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
    Route::middleware(['auth:sanctum', 'active-user', 'throttle:admin-api'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('password', [AuthController::class, 'changePassword']);
    });
});

Route::get('/admin/login', fn () => auth()->check() ? redirect('/admin') : view('auth.form', ['mode' => 'login']))->name('login');
Route::get('/admin/forgot-password', fn () => view('auth.form', ['mode' => 'forgot-password']));
Route::get('/admin/reset-password', fn () => view('auth.form', ['mode' => 'reset-password']));
Route::get('/auth/reset-password', fn () => view('auth.form', ['mode' => 'reset-password']));
Route::middleware(['auth:web', 'active-user'])->group(function (): void {
    Route::get('/admin', [ManagementController::class, 'dashboard']);
    Route::get('/admin/pages/{id}/preview', [ManagementController::class, 'preview'])->whereNumber('id');
    Route::get('/admin/{resource}/{action?}', [ManagementController::class, 'resource'])->where('action', 'new|[0-9]+');
});
Route::get('/manifest.webmanifest', fn () => response()->json(['id' => '/', 'name' => 'کانون کارشناسان رسمی دادگستری مازندران', 'short_name' => 'کانون مازندران', 'start_url' => '/', 'scope' => '/', 'display' => 'standalone', 'dir' => 'rtl', 'lang' => 'fa', 'theme_color' => '#00477a', 'background_color' => '#f5f8fb', 'icons' => [['src' => '/pwa/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'], ['src' => '/pwa/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png']]])->header('Content-Type', 'application/manifest+json'));
Route::get('/api/v1/media/{media}', [MediaController::class, 'show'])->whereNumber('media')->middleware('throttle:public-api');
Route::get('/backend/api/v1/media/{media}', [MediaController::class, 'show'])->whereNumber('media')->middleware('throttle:public-api');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nDisallow: /api\nSitemap: ".url('/sitemap.xml')."\n")->header('Content-Type', 'text/plain'));
Route::get('/sitemap.xml', function () {
    $entries = app()->call([app(SiteController::class), 'sitemap'])->getData(true)['data'];

    return response()->view('site.sitemap', compact('entries'))->header('Content-Type', 'application/xml');
});
Route::get('/', [PortalController::class, 'home']);
Route::get('/en', [PortalController::class, 'home']);
Route::get('/{path}', [PortalController::class, 'page'])->where('path', '(?!(?:api|sanctum|admin|build|assets|backend)(?:/|$)).+');
