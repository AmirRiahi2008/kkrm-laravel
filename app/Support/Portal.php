<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use IntlDateFormatter;

final class Portal
{
    public static function locale(): string
    {
        return app()->getLocale() === 'en' ? 'en' : 'fa';
    }

    public static function t(string $text): string
    {
        return self::locale() === 'en' ? (self::data('translations')[$text] ?? $text) : $text;
    }

    public static function data(string $file): array
    {
        static $data = [];

        return $data[$file] ??= json_decode(file_get_contents(resource_path('data/'.$file.'.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function title(array $item): string
    {
        return (string) ($item['title'] ?? $item['name'] ?? $item['question'] ?? $item['subject'] ?? $item['bank_name'] ?? trim(($item['first_name'] ?? '').' '.($item['last_name'] ?? '')));
    }

    public static function href(?string $href): ?string
    {
        if (! $href || preg_match('/[\\\\\x00-\x1f]/', $href)) {
            return null;
        }
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return $href;
        }
        $url = parse_url($href);

        return $url && in_array($url['scheme'] ?? '', ['http', 'https'], true) && isset($url['host']) && ! isset($url['user']) && ! isset($url['pass']) ? $href : null;
    }

    public static function url(string $path): string
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('#^/(api|admin|assets|build|pwa|backend)(/|$)#', $path)) {
            return $path;
        }

        return self::locale() === 'en' && ! preg_match('#^/en(/|$)#', $path) ? '/en'.($path === '/' ? '' : $path) : $path;
    }

    public static function path(string $resource, array $item): string
    {
        if ($resource === 'pages' && self::href($item['public_path'] ?? $item['path'] ?? null)) {
            return self::url($item['public_path'] ?? $item['path']);
        }
        if ($resource === 'news' && ($item['type'] ?? '') === 'article') {
            $resource = 'articles';
        }

        return self::url('/'.$resource.'/'.rawurlencode((string) ($item['slug'] ?? $item['id'])));
    }

    public static function media(mixed $item): ?string
    {
        return is_array($item) && ! empty($item['id']) ? '/api/v1/media/'.(int) $item['id'] : null;
    }

    public static function date(mixed $date, bool $full = false): string
    {
        if (! $date) {
            return '';
        }
        try {
            $value = Carbon::parse($date)->setTimezone('Asia/Tehran');
        } catch (\Throwable) {
            return '';
        }
        $format = new IntlDateFormatter(self::locale() === 'fa' ? 'fa_IR@calendar=persian' : 'en_US', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Tehran', self::locale() === 'fa' ? IntlDateFormatter::TRADITIONAL : IntlDateFormatter::GREGORIAN, $full ? 'EEEE d MMMM yyyy HH:mm' : 'yyyy/MM/dd');

        return $format->format($value);
    }

    public static function number(mixed $value): string
    {
        return (new \NumberFormatter(self::locale() === 'fa' ? 'fa_IR' : 'en_US', \NumberFormatter::DECIMAL))->format($value ?? 0);
    }

    public static function datePart(string $pattern, mixed $date = null): string
    {
        $format = new IntlDateFormatter(self::locale() === 'fa' ? 'fa_IR@calendar=persian' : 'en_US', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Tehran', self::locale() === 'fa' ? IntlDateFormatter::TRADITIONAL : IntlDateFormatter::GREGORIAN, $pattern);

        return $format->format(Carbon::parse($date ?? now()));
    }

    public static function adminDate(mixed $date, bool $weekday = false): string
    {
        if (! $date) {
            return '—';
        }

        return self::datePart($weekday ? 'yyyy MMMM dd, EEEE ساعت HH:mm' : 'yyyy/MM/dd, HH:mm', $date);
    }

    public static function active(?string $url): bool
    {
        if (! $url || ! str_starts_with($url, '/')) {
            return false;
        }
        $parts = parse_url($url);
        $path = preg_replace('#^/en(?=/|$)#', '', request()->getPathInfo()) ?: '/';
        $target = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $query);
        foreach ($query as $key => $value) {
            if ((string) request($key) !== (string) $value) {
                return false;
            }
        }

        return $path === $target || ($target !== '/' && str_starts_with($path, $target.'/'));
    }

    public static function branch(array $item): bool
    {
        if (self::active($item['url'] ?? null)) {
            return true;
        } foreach ($item['children'] ?? [] as $child) {
            if (self::branch($child)) {
                return true;
            }
        }

return false;
    }

    public static function can(string $resource): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($resource === 'security' || $resource === 'media') {
            return true;
        }
        if ($resource === 'users') {
            return $user->role->value === 'super_admin';
        }

        return $user->role->value !== 'editor' || $resource === 'posts';
    }
}
