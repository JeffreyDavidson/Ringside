<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

beforeEach(fn () => Storage::fake('local'));

test('the released poster streams the approved bytes with private security headers', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    savePublicDraft($fixture['event'], [
        'poster' => UploadedFile::fake()->image('poster.png'),
        'poster_alt' => 'Approved poster',
    ]);
    $publication = releasePublicPage($fixture['event']);
    $key = publicPosterKey($publication);
    $bytes = Storage::disk('local')->get('event-posters/'.$key) ?? throw new RuntimeException('Expected poster bytes');
    auth()->logout();

    // Act
    $response = get(route('shows.poster', [
        'publicId' => $publication->public_id,
        'slug' => $publication->slug,
        'asset' => $key,
    ]));

    // Assert
    $response
        ->assertOk()
        ->assertStreamedContent($bytes)
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('missing or unapproved poster assets are not served', function (string $case): void {
    // Arrange
    $fixture = publicEventFixture();
    savePublicDraft($fixture['event'], [
        'poster' => UploadedFile::fake()->image('poster.png'),
        'poster_alt' => 'Approved poster',
    ]);
    $publication = releasePublicPage($fixture['event']);
    $key = publicPosterKey($publication);
    if ($case === 'file missing') {
        Storage::disk('local')->delete('event-posters/'.$key);
    }
    $asset = $case === 'wrong key' ? '00000000-0000-4000-8000-000000000000.png' : $key;
    $slug = $case === 'wrong slug' ? 'wrong-slug' : $publication->slug;
    auth()->logout();

    // Act
    $response = get(route('shows.poster', [
        'publicId' => $publication->public_id,
        'slug' => $slug,
        'asset' => $asset,
    ]));

    // Assert
    $response->assertNotFound();
})->with([
    'file missing' => 'file missing',
    'wrong key' => 'wrong key',
    'wrong slug' => 'wrong slug',
]);
