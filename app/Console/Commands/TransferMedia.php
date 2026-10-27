<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class TransferMedia extends Command
{
    protected $signature = 'media:transfer {--from=local} {--to=local} {--source-root=} {--id=*} {--apply}';

    protected $description = 'Copy and verify media before updating disk references; dry run unless --apply is supplied';

    public function handle(MediaStorageService $files): int
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        $root = $this->option('source-root');
        $ids = $this->option('id');

        if (! config('filesystems.disks.'.$from) || ! config('filesystems.disks.'.$to)) {
            $this->error('Both source and destination disks must be configured.');

            return self::INVALID;
        }

        if ($root !== null && (! is_dir($root) || ! is_readable($root))) {
            $this->error('The source root must be a readable directory containing the media folder.');

            return self::INVALID;
        }

        if ($from === $to && $root === null) {
            $this->error('For a repair on the same disk, specify --source-root.');

            return self::INVALID;
        }

        if (array_filter($ids, fn ($id) => ! ctype_digit((string) $id) || (int) $id < 1)) {
            $this->error('Each --id must be a positive integer.');

            return self::INVALID;
        }

        $source = $root !== null
            ? Storage::build(['driver' => 'local', 'root' => realpath($root), 'throw' => true])
            : Storage::disk($from);
        $destination = Storage::disk($to);
        $apply = (bool) $this->option('apply');
        $query = Media::query()->where('disk', $from)->when($ids !== [], fn ($query) => $query->whereIn('id', $ids));
        $ready = 0;
        $failed = 0;

        $this->info($apply ? 'Copying verified files. Source files will be retained.' : 'Dry run. No files or database records will be changed.');

        foreach ($query->lazyById(100) as $record) {
            try {
                $transfer = function () use ($record, $files, $source, $destination, $from, $to, $apply): void {
                    $media = $apply ? Media::query()->lockForUpdate()->findOrFail($record->id) : $record;
                    if ($media->disk !== $from || $media->path !== $record->path || $media->sha256 !== $record->sha256) {
                        throw new RuntimeException('The media record changed; run the command again.');
                    }

                    $path = $files->path($media);
                    if (! $source->exists($path)) {
                        throw new RuntimeException('Source file is missing. Run media:audit to locate it.');
                    }

                    $fingerprint = $files->fingerprint($source, $path);
                    $files->verify($media, $fingerprint);
                    $exists = $destination->exists($path);

                    if ($exists) {
                        $files->verify($media, $files->fingerprint($destination, $path));
                    }

                    if (! $apply) {
                        return;
                    }

                    if (! $exists) {
                        $stream = $source->readStream($path);
                        if (! is_resource($stream)) {
                            throw new RuntimeException('Source file could not be opened.');
                        }
                        try {
                            if (! $destination->put($path, $stream, ['visibility' => 'private', 'ContentType' => $media->mime_type])) {
                                throw new RuntimeException('Destination write failed.');
                            }
                        } finally {
                            fclose($stream);
                        }
                        $files->verify($media, $files->fingerprint($destination, $path));
                    }

                    if ($media->disk !== $to) {
                        $media->forceFill(['disk' => $to])->saveQuietly();
                    }
                };

                if ($apply) {
                    DB::transaction($transfer);
                } else {
                    $transfer();
                }

                $ready++;
                $this->line('Media '.$record->id.': '.($apply ? 'verified and available on '.$to : 'ready for '.$to));
            } catch (Throwable $exception) {
                $failed++;
                $message = get_class($exception) === RuntimeException::class ? $exception->getMessage() : 'Storage or database operation failed; check configuration, connectivity and permissions.';
                $this->error('Media '.$record->id.': '.$message);
            }
        }

        $this->line('Ready: '.$ready.' | Failed: '.$failed);
        if (! $apply && $failed === 0 && $ready > 0) {
            $this->info('Run the same command with --apply to copy the verified files and update disk references.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
