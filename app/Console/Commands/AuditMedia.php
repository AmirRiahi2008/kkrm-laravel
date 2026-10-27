<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class AuditMedia extends Command
{
    protected $signature = 'media:audit {--verify} {--id=*} {--source-root=} {--limit=20}';

    protected $description = 'Check media records against their storage without changing files or the database';

    public function handle(MediaStorageService $files): int
    {
        $ids = $this->option('id');
        if (array_filter($ids, fn ($id) => ! ctype_digit((string) $id) || (int) $id < 1)) {
            $this->error('Each --id must be a positive integer.');

            return self::INVALID;
        }

        $extra = $this->option('source-root');
        if ($extra !== null && ! is_dir($extra)) {
            $this->error('The source root must be an existing directory containing the media folder.');

            return self::INVALID;
        }

        $query = Media::query()->when($ids !== [], fn ($query) => $query->whereIn('id', $ids));
        $counts = ['ready' => 0, 'missing' => 0, 'invalid' => 0];
        $rows = [];
        $limit = max(1, min(500, (int) $this->option('limit')));

        foreach ($query->lazyById(100) as $media) {
            try {
                $path = $files->path($media);
                $disk = Storage::disk($media->disk);

                if ($disk->exists($path)) {
                    if ($this->option('verify')) {
                        $files->verify($media, $files->fingerprint($disk, $path));
                    }
                    $counts['ready']++;

                    continue;
                }

                $counts['missing']++;
                $locations = [];

                foreach ($files->localRoots($extra) as $root) {
                    if (is_file($root.'/'.$path) && is_readable($root.'/'.$path)) {
                        $locations[] = $root;
                    }
                }

                if (count($rows) < $limit) {
                    $rows[] = [$media->id, $media->disk, 'missing', $path, implode(' | ', $locations) ?: 'No matching file found'];
                }
            } catch (Throwable $exception) {
                $counts['invalid']++;
                if (count($rows) < $limit) {
                    $rows[] = [$media->id, $media->disk, 'invalid', $media->path, get_class($exception) === \RuntimeException::class ? $exception->getMessage() : 'Storage access failed; check configuration and permissions'];
                }
            }
        }

        if ($rows !== []) {
            $this->table(['ID', 'Disk', 'Status', 'Relative path', 'Matching source root / error'], $rows);
        }

        $this->line('Ready: '.$counts['ready'].' | Missing: '.$counts['missing'].' | Invalid: '.$counts['invalid']);
        $this->line('No files or records were changed. Only non-deleted media records were checked.');

        return $counts['missing'] + $counts['invalid'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
