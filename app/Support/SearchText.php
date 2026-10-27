<?php

declare(strict_types=1);

namespace App\Support;

use Normalizer;

final class SearchText
{
    public static function normalize(string $value): string
    {
        $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        $value = str_replace(['ي', 'ى', 'ك', '%', '_'], ['ی', 'ی', 'ک', '', ''], $value);
        $value = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $value);

        return trim(preg_replace('/\s+/u', ' ', $value));
    }

    public static function fromPost(string $title, ?string $excerpt, string $body): string
    {
        $body = preg_replace('/<[^>]*>/u', ' ', $body);

        return self::normalize(html_entity_decode($title.' '.($excerpt ?? '').' '.$body, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
