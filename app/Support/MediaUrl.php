<?php

declare(strict_types=1);

namespace App\Support;

final class MediaUrl
{
    public static function relative(string $url): ?string
    {
        $parts = parse_url(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if (! preg_match('~\A/(?:backend/)?api/v1/media/([1-9][0-9]*)\z~D', $path, $matches)) {
            return null;
        }

        if (! isset($parts['host']) && ! isset($parts['scheme']) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return '/api/v1/media/'.$matches[1];
        }

        if (! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || ! isset($parts['host'])) {
            return null;
        }

        $origins = [config('app.url'), config('kkrm.frontend_url'), ...config('kkrm.legacy_media_origins', [])];

        foreach ($origins as $origin) {
            $allowed = parse_url((string) $origin);
            if (! is_array($allowed) || ! isset($allowed['scheme'], $allowed['host'])) {
                continue;
            }

            $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
            $allowedPort = $allowed['port'] ?? ($allowed['scheme'] === 'https' ? 443 : 80);

            if (strtolower($parts['scheme']) === strtolower($allowed['scheme']) && strtolower($parts['host']) === strtolower($allowed['host']) && $port === $allowedPort) {
                return '/api/v1/media/'.$matches[1];
            }
        }

        return null;
    }
}
