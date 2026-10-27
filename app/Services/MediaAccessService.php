<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AlbumItem;
use App\Models\Media;
use App\Models\Post;
use App\Models\Setting;
use App\Support\ContentCatalog;
use App\Support\MediaUrl;
use DOMDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class MediaAccessService
{
    private const REFERENCES = [
        'posts' => ['cover_id'],
        'pages' => ['cover_id'],
        'documents' => ['file_id'],
        'experts' => ['photo_id'],
        'board-members' => ['photo_id'],
        'agreements' => ['cover_id', 'file_id'],
        'albums' => ['cover_id'],
        'events' => ['cover_id'],
        'slides' => ['image_id'],
    ];

    private const HTML_REFERENCES = [
        'posts' => ['body'],
        'pages' => ['body'],
        'documents' => ['body'],
        'agreements' => ['body'],
        'events' => ['body'],
        'experts' => ['biography'],
        'board-members' => ['biography'],
        'faqs' => ['answer'],
    ];

    public function public(Media $media): bool
    {
        if (
            config('kkrm.clamav_enabled')
            && $media->scan_status !== 'clean'
        ) {
            return false;
        }

        if (
            Setting::whereIn('key', [
                'logo_media_id',
                'favicon_media_id',
            ])->get()->contains(
                fn ($setting) => (int) $setting->value === $media->id,
            )
        ) {
            return true;
        }

        foreach (self::REFERENCES as $resource => $columns) {
            $query = app(PublicQuery::class)->for($resource);

            if (
                $query->where(
                    function (Builder $query) use ($columns, $media): void {
                        foreach ($columns as $column) {
                            $query->orWhere($column, $media->id);
                        }
                    },
                )->exists()
            ) {
                return true;
            }
        }

        if (
            app(PublicQuery::class)
                ->for('posts')
                ->whereHas(
                    'attachments',
                    fn (Builder $query) => $query->where(
                        'media.id',
                        $media->id,
                    ),
                )
                ->exists()
        ) {
            return true;
        }

        return $this->embedded($media, true)
            || AlbumItem::where('media_id', $media->id)
                ->whereIn(
                    'album_id',
                    app(PublicQuery::class)->for('albums')->select('id'),
                )
                ->exists();
    }

    public function referenced(Media $media): bool
    {
        if (
            Setting::whereIn('key', [
                'logo_media_id',
                'favicon_media_id',
            ])->get()->contains(
                fn ($setting) => (int) $setting->value === $media->id,
            )
        ) {
            return true;
        }

        foreach (self::REFERENCES as $resource => $columns) {
            $query = $resource === 'posts'
                ? Post::withTrashed()
                : ContentCatalog::model($resource)
                    ->newQuery()
                    ->withTrashed();

            if (
                $query->where(
                    function (Builder $query) use ($columns, $media): void {
                        foreach ($columns as $column) {
                            $query->orWhere($column, $media->id);
                        }
                    },
                )->exists()
            ) {
                return true;
            }
        }

        return $this->embedded($media, false)
            || AlbumItem::where('media_id', $media->id)->exists()
            || DB::table('post_media')
                ->where('media_id', $media->id)
                ->exists();
    }

    private function embedded(Media $media, bool $publicOnly): bool
    {
        if (
            ! str_starts_with($media->mime_type, 'image/')
            && $media->mime_type !== 'application/pdf'
        ) {
            return false;
        }

        foreach (self::HTML_REFERENCES as $resource => $columns) {
            $query = $publicOnly
                ? app(PublicQuery::class)->for($resource)
                : ($resource === 'posts'
                    ? Post::withTrashed()
                    : ContentCatalog::model($resource)
                        ->newQuery()
                        ->withTrashed());

            $query->where(
                function (Builder $query) use ($columns, $media): void {
                    foreach ($columns as $column) {
                        $query->orWhere(
                            $column,
                            'like',
                            '%/api/v1/media/'.$media->id.'%',
                        );
                    }
                    $query->orWhereHas(
                        'localizedContent',
                        function (Builder $query) use ($columns, $media): void {
                            $query->where(function (Builder $query) use ($columns, $media): void {
                                foreach ($columns as $column) {
                                    $query->orWhere(
                                        'fields->'.$column,
                                        'like',
                                        '%/api/v1/media/'.$media->id.'%',
                                    );
                                }
                            });
                        },
                    );
                },
            );

            $records = $query
                ->select(array_merge(['id'], $columns))
                ->with('localizedContent')
                ->lazyById(100);

            foreach ($records as $record) {
                foreach ($columns as $column) {
                    if (
                        $this->containsMedia(
                            (string) $record->getAttribute($column),
                            $media,
                        )
                    ) {
                        return true;
                    }
                }
                foreach ($record->localizedContent as $translation) {
                    foreach ($columns as $column) {
                        if ($this->containsMedia(
                            (string) ($translation->fields[$column] ?? ''),
                            $media,
                        )) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function containsMedia(string $html, Media $media): bool
    {
        if ($html === '') {
            return false;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument;

            if (! $document->loadHTML($html, LIBXML_NONET)) {
                return false;
            }

            $pdf = $media->mime_type === 'application/pdf';
            foreach ($document->getElementsByTagName($pdf ? 'a' : 'img') as $element) {
                $url = $element->getAttribute($pdf ? 'href' : 'src');
                if (MediaUrl::relative($url) === '/api/v1/media/'.$media->id) {
                    return true;
                }
            }

            return false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
