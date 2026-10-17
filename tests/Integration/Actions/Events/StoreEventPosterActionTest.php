<?php

declare(strict_types=1);

use App\Actions\Events\StoreEventPosterAction;
use App\Exceptions\Events\EventPublicationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Double;

beforeEach(fn () => Storage::fake('local'));

test('poster processing honors all camera orientations and strips metadata', function (int $orientation, int $width, int $height, int $red, int $green, int $blue): void {
    $pixels = imagecreatetruecolor(30, 50);
    foreach ([[0, 0, 14, 24, 255, 0, 0], [15, 0, 29, 24, 0, 255, 0], [0, 25, 14, 49, 0, 0, 255], [15, 25, 29, 49, 255, 255, 0]] as [$x1,$y1,$x2,$y2,$r,$g,$b]) {
        imagefilledrectangle($pixels, $x1, $y1, $x2, $y2, imagecolorallocate($pixels, $r, $g, $b) ?: throw new RuntimeException('Cannot allocate test color'));
    }
    ob_start();
    imagejpeg($pixels, null, 100);
    $original = (string) ob_get_clean();
    $exif = "Exif\0\0II".pack('v', 42).pack('V', 8).pack('v', 1).pack('v', 0x112).pack('v', 3).pack('V', 1).pack('v', $orientation)."\0\0".pack('V', 0).'LEAK-GPS-7Q';
    $jpeg = substr($original, 0, 2)."\xff\xe1".pack('n', strlen($exif) + 2).$exif.substr($original, 2);
    $upload = UploadedFile::fake()->createWithContent('camera.jpg', $jpeg);

    $key = app(StoreEventPosterAction::class)->handle($upload);

    $bytes = Storage::disk('local')->get('event-posters/'.$key) ?? throw new RuntimeException('Missing poster');
    $dimensions = getimagesizefromstring($bytes) ?: throw new RuntimeException('Invalid poster');
    $decoded = imagecreatefromstring($bytes) ?: throw new RuntimeException('Invalid pixel output');
    $color = imagecolorsforindex($decoded, imagecolorat($decoded, 4, 4) ?: throw new RuntimeException('Cannot read test color'));
    expect($dimensions[0])->toBe($width)->and($dimensions[1])->toBe($height)
        ->and($bytes)->not->toContain('Exif')->not->toContain('LEAK-GPS-7Q')
        ->and(abs($color['red'] - $red))->toBeLessThan(5)->and(abs($color['green'] - $green))->toBeLessThan(5)->and(abs($color['blue'] - $blue))->toBeLessThan(5);
})->with([[1, 30, 50, 255, 0, 0], [2, 30, 50, 0, 255, 0], [3, 30, 50, 255, 255, 0], [4, 30, 50, 0, 0, 255], [5, 50, 30, 255, 0, 0], [6, 50, 30, 0, 0, 255], [7, 50, 30, 255, 255, 0], [8, 50, 30, 0, 255, 0]]);

test('decoding failures are reported as business validation rather than image warnings', function (): void {
    $file = UploadedFile::fake()->createWithContent('broken.png', 'not image bytes');

    expect(fn () => app(StoreEventPosterAction::class)->handle($file))->toThrow(EventPublicationException::class);
});

test('a decompression sized image is refused before conversion', function (): void {
    $file = UploadedFile::fake()->image('large.png', 4096, 2049);

    expect(fn () => app(StoreEventPosterAction::class)->handle($file))->toThrow(EventPublicationException::class);
});

test('a failed private disk write cannot supply a publication asset key', function (): void {
    $file = UploadedFile::fake()->image('poster.png');
    $disk = Double::for(FilesystemAdapter::class);
    $disk->expects('put')->returns(false);
    Storage::set('local', $disk);

    expect(fn () => app(StoreEventPosterAction::class)->handle($file))->toThrow(EventPublicationException::class);
    $disk->verify();
});

test('animated pngs cannot be silently reduced to their first frame', function (): void {
    $png = UploadedFile::fake()->image('poster.png', 30, 50)->getContent();
    $control = 'acTL'.pack('N', 2).pack('N', 0);
    $animated = substr($png, 0, 33).pack('N', 8).$control.pack('N', crc32($control)).substr($png, 33);
    $file = UploadedFile::fake()->createWithContent('animated.png', $animated);

    expect(fn () => app(StoreEventPosterAction::class)->handle($file))->toThrow(EventPublicationException::class);
});

test('a readable header with corrupt pixel data is refused without warnings or a stored asset', function (): void {
    // Arrange
    $png = UploadedFile::fake()->image('poster.png', 30, 50)->getContent();
    $file = UploadedFile::fake()->createWithContent('truncated.png', substr($png, 0, 33));

    // Act / Assert
    expect(fn () => app(StoreEventPosterAction::class)->handle($file))->toThrow(EventPublicationException::class)
        ->and(Storage::disk('local')->allFiles('event-posters'))->toBeEmpty();
});

test('poster processing limits dimensions without cropping the complete image', function (): void {
    $file = UploadedFile::fake()->image('tall.png', 1000, 3000);

    $key = app(StoreEventPosterAction::class)->handle($file);

    $dimensions = getimagesizefromstring(Storage::disk('local')->get('event-posters/'.$key) ?? throw new RuntimeException('Missing sanitized image')) ?: throw new RuntimeException('Invalid sanitized image');
    expect($dimensions[0])->toBe(683)->and($dimensions[1])->toBe(2048);
});
