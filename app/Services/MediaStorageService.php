<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

final class MediaStorageService
{
    public function path(Media $media): string
    {
        $path = (string) $media->path;

        if ($path === '' || str_starts_with($path, '/') || preg_match('/[\\\\\x00-\x1f\x7f]/', $path)) {
            throw new RuntimeException('Invalid relative media path.');
        }

        foreach (explode('/', $path) as $segment) {
            if (in_array($segment, ['', '.', '..'], true)) {
                throw new RuntimeException('Invalid relative media path.');
            }
        }

        return $path;
    }

    public function fingerprint(Filesystem $disk, string $path): array
    {
        $stream = $disk->readStream($path);

        if (! is_resource($stream)) {
            throw new RuntimeException('The media file could not be read.');
        }

        try {
            $context = hash_init('sha256');
            $size = hash_update_stream($context, $stream);

            if ($size === false) {
                throw new RuntimeException('The media file could not be verified.');
            }

            return ['sha256' => hash_final($context), 'size' => $size];
        } finally {
            fclose($stream);
        }
    }

    public function verify(Media $media, array $fingerprint): void
    {
        if ((int) $media->size !== $fingerprint['size']) {
            throw new RuntimeException('File size does not match the media record.');
        }

        if (! hash_equals(strtolower((string) $media->sha256), $fingerprint['sha256'])) {
            throw new RuntimeException('File checksum does not match the media record.');
        }
    }

    public function localRoots(?string $extra = null): array
    {
        $roots = [
            $extra,
            config('filesystems.disks.local.root'),
            config('filesystems.disks.public.root'),
            storage_path('app'),
            storage_path(),
        ];

        return array_values(array_unique(array_filter(array_map(
            fn ($root) => is_string($root) && is_dir($root) ? realpath($root) : null,
            $roots,
        ))));
    }
}
