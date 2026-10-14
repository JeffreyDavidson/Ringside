<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Events\EventPublicationException;
use ErrorException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreEventPosterAction
{
    /** Re-encode pixels into a fresh PNG; no original filename or binary metadata is retained. */
    public function handle(UploadedFile $file): string
    {
        $bytes = $file->getContent();
        set_error_handler(static function (int $severity, string $message, string $filename, int $line): never {
            throw new ErrorException($message, 0, $severity, $filename, $line);
        });
        try {
            $size = getimagesizefromstring($bytes);
            if ($size === false || $size[0] * $size[1] > 8_000_000 || (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 8) === 'WEBPVP8X' && strlen($bytes) > 20 && (ord($bytes[20]) & 0x02) !== 0)
                || (str_starts_with($bytes, "\x89PNG") && str_contains($bytes, "\0\0\0\x08acTL"))) {
                throw EventPublicationException::poster();
            }
            $image = imagecreatefromstring($bytes) ?: throw EventPublicationException::poster();
            if (str_contains($bytes, "Exif\0\0") && function_exists('exif_read_data')) {
                $metadata = exif_read_data($file->getPathname());
                $orientation = (int) ($metadata['Orientation'] ?? 1);
                if (in_array($orientation, [2, 4, 5, 7], true)) {
                    imageflip($image, in_array($orientation, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
                }
                $angle = match ($orientation) {
                    3 => 180,5,6,7 => -90,8 => 90,default => 0
                };
                if ($angle !== 0) {
                    $image = imagerotate($image, $angle, 0) ?: throw EventPublicationException::poster();
                }
            }
            $width = imagesx($image);
            $height = imagesy($image);
            if (max($width, $height) > 2048) {
                $image = imagescale($image, (int) round($width * 2048 / max($width, $height)), (int) round($height * 2048 / max($width, $height))) ?: throw EventPublicationException::poster();
            }
            imagesavealpha($image, true);
            ob_start();
            try {
                imagepng($image);
                $encoded = (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } catch (ErrorException|\ValueError) {
            throw EventPublicationException::poster();
        } finally {
            restore_error_handler();
        }
        $key = Str::uuid().'.png';
        if (! Storage::disk('local')->put('event-posters/'.$key, $encoded)) {
            throw EventPublicationException::poster();
        }

        return $key;
    }
}
