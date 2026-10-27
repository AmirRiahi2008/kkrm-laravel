<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

final class MediaService
{
    public function upload(UploadedFile $file, User $actor, ?string $alt): Media
    {
        $mime = $file->getMimeType();
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];
        if (! isset($extensions[$mime])) {
            throw ValidationException::withMessages([
                'file' => 'فقط تصاویر JPEG، PNG، WebP و فایل PDF پذیرفته می‌شوند.',
            ]);
        }
        $bytes = file_get_contents($file->getRealPath());
        abort_if($bytes === false, 503);
        if (strlen($bytes) > max(1, (int) config('kkrm.media_max_kb')) * 1024) {
            throw ValidationException::withMessages(['file' => 'حجم فایل بیش از حد مجاز است.']);
        }
        if ($mime === 'application/pdf') {
            $validHeader = preg_match('/\A%PDF-(?:1\.[0-7]|2\.0)(?:\r\n|\r|\n)/', $bytes) === 1;
            $validEnding = preg_match('/%%EOF[\x00\x09\x0a\x0c\x0d\x20]*\z/', substr($bytes, -2048)) === 1;
            if (! $validHeader || ! $validEnding) {
                throw ValidationException::withMessages(['file' => 'فایل PDF معتبر نیست یا بارگذاری آن ناقص بوده است.']);
            }
        }
        if (str_starts_with($mime, 'image/')) {
            if (strlen($bytes) > 10485760) {
                throw ValidationException::withMessages(['file' => 'حجم تصویر نباید بیش از ۱۰ مگابایت باشد.']);
            }
            $size = @getimagesizefromstring($bytes);
            if (! $size || $size[0] > 8192 || $size[1] > 8192 || $size[0] * $size[1] > 24000000) {
                throw ValidationException::withMessages(['file' => 'ابعاد تصویر معتبر نیست یا بیش از حد بزرگ است.']);
            }
            $image = @imagecreatefromstring($bytes);
            if (! $image) {
                throw ValidationException::withMessages(['file' => 'تصویر معتبر نیست.']);
            }
            imagealphablending($image, false);
            imagesavealpha($image, true);
            ob_start();
            match ($mime) {
                'image/jpeg' => imagejpeg($image, null, 90),
                'image/png' => imagepng($image),
                'image/webp' => imagewebp($image, null, 90),
            };
            $bytes = ob_get_clean();
            imagedestroy($image);
        }
        $scanStatus = 'unchecked';
        if (config('kkrm.clamav_enabled')) {
            $process = new Process([config('kkrm.clamav_binary'), '--no-summary', '--', $file->getRealPath()]);
            $process->setTimeout(30);
            try {
                $process->run();
            } catch (ExceptionInterface) {
                abort(503);
            }
            if ($process->getExitCode() === 1) {
                throw ValidationException::withMessages(['file' => 'فایل تأیید امنیتی نشد.']);
            }
            abort_unless($process->isSuccessful(), 503);
            $scanStatus = 'clean';
        }
        $disk = config('kkrm.media_disk');
        $path = 'media/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extensions[$mime];
        abort_unless(Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private']), 503);
        try {
            return DB::transaction(fn () => Media::create([
                'uploaded_by' => $actor->id, 'disk' => $disk, 'path' => $path,
                'original_name' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 250),
                'mime_type' => $mime, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes),
                'alt' => $alt, 'scan_status' => $scanStatus,
            ]));
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }
}
