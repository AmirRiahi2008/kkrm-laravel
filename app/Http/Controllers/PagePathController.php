<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentPresenter;
use App\Services\PublicQuery;
use App\Support\ApiResponse;
use App\Support\PagePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PagePathController
{
    public function __invoke(Request $request, PublicQuery $queries, ContentPresenter $present): JsonResponse
    {
        $data = $request->validate(['path' => ['required', 'string', 'max:240']]);
        $path = PagePath::normalize($data['path']);
        abort_unless(PagePath::valid($path), 404);
        $page = $queries->for('pages')->where('path', $path)->firstOrFail();

        return ApiResponse::success($present->one($page));
    }
}
