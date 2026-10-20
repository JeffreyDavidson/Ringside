<?php

declare(strict_types=1);

use App\Support\Images\ImageReencoder;
use App\Support\Images\ImageReencodingException;

/** Encodes a four-quadrant test image (red, green / blue, yellow) in the given GD format. */
function reencoderFixture(string $format, int $width = 30, int $height = 50): string
{
    $pixels = imagecreatetruecolor(max(1, $width), max(1, $height));
    $halfWidth = intdiv($width, 2);
    $halfHeight = intdiv($height, 2);
    imagefilledrectangle($pixels, 0, 0, $halfWidth - 1, $halfHeight - 1, (int) imagecolorallocate($pixels, 255, 0, 0));
    imagefilledrectangle($pixels, $halfWidth, 0, $width - 1, $halfHeight - 1, (int) imagecolorallocate($pixels, 0, 255, 0));
    imagefilledrectangle($pixels, 0, $halfHeight, $halfWidth - 1, $height - 1, (int) imagecolorallocate($pixels, 0, 0, 255));
    imagefilledrectangle($pixels, $halfWidth, $halfHeight, $width - 1, $height - 1, (int) imagecolorallocate($pixels, 255, 255, 0));

    ob_start();
    match ($format) {
        'png' => imagepng($pixels),
        'jpeg' => imagejpeg($pixels, null, 100),
        'webp' => imagewebp($pixels, null, 100),
        'gif' => imagegif($pixels),
        default => throw new InvalidArgumentException("Unknown format {$format}"),
    };

    return (string) ob_get_clean();
}

/** Inserts an EXIF orientation block (and a marker string that must never survive) after the JPEG SOI marker. */
function reencoderJpegWithOrientation(int $orientation): string
{
    $jpeg = reencoderFixture('jpeg');
    $exif = "Exif\0\0II".pack('v', 42).pack('V', 8).pack('v', 1).pack('v', 0x112).pack('v', 3).pack('V', 1).pack('v', $orientation)."\0\0".pack('V', 0).'LEAK-GPS-7Q';

    return substr($jpeg, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
}

/** @return array{0: int, 1: int} */
function reencoderDimensions(string $png): array
{
    $size = getimagesizefromstring($png) ?: throw new RuntimeException('Not an image');

    return [$size[0], $size[1]];
}

test('re-encodes supported formats into a png of the same size', function (string $format): void {
    // Arrange
    $reencoder = new ImageReencoder;
    $bytes = reencoderFixture($format);

    // Act
    $png = $reencoder->reencode($bytes);

    // Assert
    expect($png)->toStartWith("\x89PNG")
        ->and(reencoderDimensions($png))->toBe([30, 50]);
})->with(['png', 'jpeg', 'webp', 'gif']);

test('limits the longest edge to 2048 pixels and keeps the whole image', function (int $width, int $height, array $expected): void {
    // Arrange
    $reencoder = new ImageReencoder;
    $bytes = reencoderFixture('png', $width, $height);

    // Act
    $png = $reencoder->reencode($bytes);

    // Assert
    expect(reencoderDimensions($png))->toBe($expected);
})->with([
    'tall' => [1000, 3000, [683, 2048]],
    'wide' => [3000, 1000, [2048, 683]],
    'exactly at the limit' => [2048, 1000, [2048, 1000]],
]);

test('applies every camera orientation and strips metadata', function (int $orientation, array $dimensions, array $topLeft): void {
    // Arrange
    $reencoder = new ImageReencoder;
    $bytes = reencoderJpegWithOrientation($orientation);

    // Act
    $png = $reencoder->reencode($bytes);

    // Assert
    $decoded = imagecreatefromstring($png) ?: throw new RuntimeException('Not an image');
    $color = imagecolorsforindex($decoded, (int) imagecolorat($decoded, 4, 4));
    expect(reencoderDimensions($png))->toBe($dimensions)
        ->and($png)->not->toContain('Exif')->not->toContain('LEAK-GPS-7Q')
        ->and([abs($color['red'] - $topLeft[0]) < 5, abs($color['green'] - $topLeft[1]) < 5, abs($color['blue'] - $topLeft[2]) < 5])->toBe([true, true, true]);
})->with([
    'normal' => [1, [30, 50], [255, 0, 0]],
    'mirrored' => [2, [30, 50], [0, 255, 0]],
    'upside down' => [3, [30, 50], [255, 255, 0]],
    'flipped vertically' => [4, [30, 50], [0, 0, 255]],
    'transposed' => [5, [50, 30], [255, 0, 0]],
    'rotated clockwise' => [6, [50, 30], [0, 0, 255]],
    'transversed' => [7, [50, 30], [255, 255, 0]],
    'rotated anticlockwise' => [8, [50, 30], [0, 255, 0]],
]);

test('refuses bytes that cannot be reencoded', function (Closure $bytes): void {
    // Arrange
    $reencoder = new ImageReencoder;

    // Act
    $attempt = fn (): string => $reencoder->reencode($bytes());

    // Assert
    expect($attempt)->toThrow(ImageReencodingException::class);
})->with([
    'not an image' => [fn (): string => 'not image bytes'],
    'empty' => [fn (): string => ''],
    'svg' => [fn (): string => '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>'],
    'truncated png header' => [fn (): string => substr(reencoderFixture('png'), 0, 33)],
    'truncated jpeg' => [fn (): string => substr(reencoderFixture('jpeg'), 0, 100)],
    'more than 8 million pixels' => [fn (): string => reencoderFixture('png', 4096, 2049)],
    'animated png' => [function (): string {
        $png = reencoderFixture('png');
        $control = 'acTL'.pack('N', 2).pack('N', 0);

        return substr($png, 0, 33).pack('N', 8).$control.pack('N', crc32($control)).substr($png, 33);
    }],
    'animated webp' => [function (): string {
        $chunks = substr(reencoderFixture('webp'), 12);
        $extended = 'VP8X'.pack('V', 10).chr(0x02)."\0\0\0".substr(pack('V', 29), 0, 3).substr(pack('V', 49), 0, 3).$chunks;

        return 'RIFF'.pack('V', strlen($extended) + 4).'WEBP'.$extended;
    }],
]);

test('accepts exactly 8 million pixels', function (): void {
    // Arrange
    $reencoder = new ImageReencoder;
    $bytes = reencoderFixture('png', 4000, 2000);

    // Act
    $png = $reencoder->reencode($bytes);

    // Assert
    expect(reencoderDimensions($png))->toBe([2048, 1024]);
});
