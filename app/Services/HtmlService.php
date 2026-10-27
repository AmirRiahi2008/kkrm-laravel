<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\MediaUrl;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class HtmlService
{
    public function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        if (strlen($html) > 500000) {
            throw ValidationException::withMessages([
                'body' => 'حجم متن بیش از حد مجاز است.',
            ]);
        }

        $config = (new HtmlSanitizerConfig)
            ->withMaxInputLength(500000)
            ->allowSafeElements()
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'loading'])
            ->allowElement('figure', ['data-kkr-gallery', 'data-columns'])
            ->allowElement('figcaption')
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('td', ['colspan', 'rowspan'])
            ->allowElement('th', ['colspan', 'rowspan', 'scope'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer')
            ->dropElement('iframe')
            ->dropElement('video')
            ->dropElement('audio');

        $clean = (new HtmlSanitizer($config))->sanitize($html);

        return preg_replace_callback('~\b(src|href)="([^"]*)"~i', function (array $matches): string {
            $relative = MediaUrl::relative($matches[2]);

            return $relative !== null ? $matches[1].'="'.$relative.'"' : $matches[0];
        }, $clean);
    }
}
