<?php

declare(strict_types=1);

namespace App\Support\Images;

use ErrorException;
use ValueError;

final class ImageReencoder
{
    private const int MAX_PIXELS = 8_000_000;

    private const int MAX_EDGE = 2048;

    /**
     * Re-encode the pixels of a still image into a fresh PNG.
     *
     * Camera orientation is applied and the longest edge is limited to 2048 pixels without cropping.
     * Nothing else from the source survives (metadata, filename, colour profile, extra frames), and
     * animated PNG and WebP, images over 8 million pixels and undecodable bytes are refused.
     *
     * @throws ImageReencodingException
     */
    public function reencode(string $bytes): string
    {
        set_error_handler(static function (int $severity, string $message, string $filename, int $line): never {
            throw new ErrorException($message, 0, $severity, $filename, $line);
        });
        try {
            $size = getimagesizefromstring($bytes);
            if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS || $this->isAnimated($bytes)) {
                throw ImageReencodingException::failed();
            }
            $image = imagecreatefromstring($bytes) ?: throw ImageReencodingException::failed();
            if (str_contains($bytes, "Exif\0\0") && function_exists('exif_read_data')) {
                $image = $this->orient($image, $this->orientationOf($bytes));
            }
            $width = imagesx($image);
            $height = imagesy($image);
            if (max($width, $height) > self::MAX_EDGE) {
                $image = imagescale($image, (int) round($width * self::MAX_EDGE / max($width, $height)), (int) round($height * self::MAX_EDGE / max($width, $height))) ?: throw ImageReencodingException::failed();
            }
            imagesavealpha($image, true);
            ob_start();
            try {
                imagepng($image);

                return (string) ob_get_contents();
            } finally {
                ob_end_clean();
            }
        } catch (ErrorException|ValueError) {
            throw ImageReencodingException::failed();
        } finally {
            restore_error_handler();
        }
    }

    /** Animated WebP (VP8X animation flag) and animated PNG (acTL chunk) would silently lose every frame but the first. */
    private function isAnimated(string $bytes): bool
    {
        return (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 8) === 'WEBPVP8X' && strlen($bytes) > 20 && (ord($bytes[20]) & 0x02) !== 0)
            || (str_starts_with($bytes, "\x89PNG") && str_contains($bytes, "\0\0\0\x08acTL"));
    }

    private function orientationOf(string $bytes): int
    {
        $stream = fopen('php://temp', 'r+') ?: throw ImageReencodingException::failed();
        fwrite($stream, $bytes);
        rewind($stream);
        try {
            $metadata = exif_read_data($stream);
        } finally {
            fclose($stream);
        }

        return (int) ($metadata['Orientation'] ?? 1);
    }

    /** Turn the pixels upright for an EXIF orientation (2-8 mirror and/or rotate; 1 and unknown values leave them alone). */
    private function orient(\GdImage $image, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, in_array($orientation, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }
        $angle = match ($orientation) {
            3 => 180,
            5, 6, 7 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        return imagerotate($image, $angle, 0) ?: throw ImageReencodingException::failed();
    }
}
