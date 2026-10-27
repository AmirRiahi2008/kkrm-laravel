<?php

declare(strict_types=1);

use App\Http\Controllers\SlideshowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'throttle:public-api'])
    ->get('api/v1/slideshow', SlideshowController::class)
    ->name('kkrm.slideshow');
