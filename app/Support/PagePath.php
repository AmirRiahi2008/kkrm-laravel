<?php

declare(strict_types=1);

namespace App\Support;

final class PagePath
{
    public const RESERVED = [
        'admin', 'api', 'backend', 'auth', 'assets', '_next', 'en', 'pwa',
        'news', 'articles', 'documents', 'albums', 'events', 'pages', 'experts',
        'expert-groups', 'disciplines', 'qualifications', 'board-members',
        'board-terms', 'agreements', 'faqs', 'bank-accounts', 'systems', 'links',
        'categories', 'tags', 'contact', 'search', 'download', 'fees', 'tariffs',
        'sanctum', 'up', 'storage', 'build',
    ];

    public static function normalize(string $path): string
    {
        $path = trim($path);
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return mb_strtolower(rtrim($path, '/')) ?: '/';
    }

    public static function valid(string $path): bool
    {
        if (mb_strlen($path) > 240 || preg_match('/^\/[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*(?:\/[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*){0,5}$/u', $path) !== 1) {
            return false;
        }

        return ! in_array(explode('/', mb_strtolower($path))[1], self::RESERVED, true);
    }
}
