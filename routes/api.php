<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExpertDirectoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PagePathController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TariffController;
use App\Support\ContentCatalog;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:public-api')->group(function (): void {
        Route::get('home', [SiteController::class, 'home']);
        Route::get('settings', [SiteController::class, 'settings']);
        Route::get('navigation', [SiteController::class, 'navigation']);
        Route::get('page-path', PagePathController::class);
        Route::get('experts/filters', [ExpertDirectoryController::class, 'filters']);
        Route::get('search', [SiteController::class, 'search'])->middleware('throttle:search');
        Route::get('redirects/resolve', [SiteController::class, 'resolveRedirect']);
        Route::get('seo/sitemap', [SiteController::class, 'sitemap']);
        Route::get('media/{media}', [MediaController::class, 'show'])->whereNumber('media');
        Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact');
        Route::post('tariffs/calculate', [TariffController::class, 'calculate'])->middleware('throttle:search');
        Route::get('{resource}', [PublicContentController::class, 'index'])->whereIn('resource', ['posts', ...ContentCatalog::PUBLIC_RESOURCES]);
        Route::get('{resource}/{key}', [PublicContentController::class, 'show'])->whereIn('resource', ['posts', ...ContentCatalog::PUBLIC_RESOURCES]);
    });
    Route::prefix('admin')->middleware(['auth:sanctum', 'active-user', 'throttle:admin-api'])->group(function (): void {
        Route::get('notifications', [Admin\NotificationController::class, 'index']);
        Route::patch('notifications/read-all', [Admin\NotificationController::class, 'readAll']);
        Route::patch('notifications/{id}/read', [Admin\NotificationController::class, 'read'])->whereUuid('id');
        Route::get('dashboard', [Admin\DashboardController::class, 'show']);
        Route::get('media', [MediaController::class, 'index']);
        Route::post('media', [MediaController::class, 'store'])->middleware('throttle:uploads');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->whereNumber('media');
        Route::get('posts/{post}/revisions', [Admin\PostController::class, 'revisions'])->whereNumber('post');
        Route::post('posts/{id}/restore', [Admin\PostController::class, 'restore'])->whereNumber('id')->middleware('can:manage-content');
        Route::apiResource('posts', Admin\PostController::class)->whereNumber('post');
        Route::middleware('can:manage-users')->group(function (): void {
            Route::apiResource('users', Admin\UserController::class)->except('destroy')->whereNumber('user');
        });
        Route::middleware('can:manage-content')->group(function (): void {
            Route::get('audit-logs', [Admin\DashboardController::class, 'audits']);
            Route::get('settings', [Admin\SettingsController::class, 'index']);
            Route::put('settings', [Admin\SettingsController::class, 'update']);
            Route::apiResource('contact-messages', Admin\ContactMessageController::class)->except('store');
            Route::put('albums/{album}/items', [Admin\AlbumItemsController::class, 'update'])->whereNumber('album');
            Route::get('{resource}', [Admin\ContentController::class, 'index'])->whereIn('resource', array_keys(ContentCatalog::MODELS));
            Route::post('{resource}', [Admin\ContentController::class, 'store'])->whereIn('resource', array_keys(ContentCatalog::MODELS));
            Route::get('{resource}/{item}', [Admin\ContentController::class, 'show'])->whereIn('resource', array_keys(ContentCatalog::MODELS))->whereNumber('item');
            Route::match(['PUT', 'PATCH'], '{resource}/{item}', [Admin\ContentController::class, 'update'])->whereIn('resource', array_keys(ContentCatalog::MODELS))->whereNumber('item');
            Route::delete('{resource}/{item}', [Admin\ContentController::class, 'destroy'])->whereIn('resource', array_keys(ContentCatalog::MODELS))->whereNumber('item');
            Route::post('{resource}/{item}/restore', [Admin\ContentController::class, 'restore'])->whereIn('resource', array_keys(ContentCatalog::MODELS))->whereNumber('item');
        });
    });
});
