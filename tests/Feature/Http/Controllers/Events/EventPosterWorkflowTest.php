<?php

declare(strict_types=1);
use App\Actions\Events\SaveEventPublicationAction;
use App\Models\Events\EventPublication;
use App\Queries\Events\PublicEventSourceQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Double;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

beforeEach(fn () => Storage::fake('local'));

test('poster metadata is stripped and draft and replaced assets never become public', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $image = UploadedFile::fake()->image('private-camera-name.png', 60, 80);
    $original = $image->getContent();
    $marker = 'LEAK-GPS-CONTACT-7Q';
    $chunk = 'tEXt'."Comment\0".$marker;
    $bytes = substr($original, 0, -12).pack('N', strlen($chunk) - 4).$chunk.pack('N', crc32($chunk)).substr($original, -12);
    $upload = UploadedFile::fake()->createWithContent('private-camera-name.png', $bytes);
    $publication = savePublicDraft($event, ['poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.']);
    $key = publicPosterKey($publication);
    expect(Storage::disk('local')->get('event-posters/'.$key))->not->toContain($marker)->not->toContain('private-camera-name');
    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response->assertOk()->assertHeader('Content-Type', 'image/png');
    $url = route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]);

    // Act
    $response = get($url);

    // Assert
    $response->assertNotFound();
    releasePublicPage($event);

    // Act
    $response = get($url);

    // Assert
    $response->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    $publication = savePublicDraft($event, ['poster' => UploadedFile::fake()->image('replacement.png', 50, 50), 'poster_alt' => 'Replacement event poster']);

    // Act
    $response = get($url);

    // Assert
    $response->assertOk();
    releasePublicPage($event);

    // Act
    $response = get($url);

    // Assert
    $response->assertNotFound();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response->assertSee('Replacement event poster')->assertDontSee($key);
    $newUrl = route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => publicPosterKey($publication)]);

    // Act
    $response = get($newUrl);

    // Assert
    $response->assertOk();
    $editor = get(route('events.public-pages.edit', $event));

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), ['complete' => 1, 'version' => EventPublication::query()->where('event_id', $event->id)->firstOrFail()->version, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'section' => 'card']);

    // Assert
    $response->assertSessionHasNoErrors();

    // Act
    $response = get($newUrl);

    // Assert
    $response->assertNotFound();
});

test('invalid uploads and missing alternative text cannot create a draft', function (string $kind): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $editor = get(route('events.public-pages.edit', $event));
    $upload = match ($kind) {
        'svg' => UploadedFile::fake()->createWithContent('active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        'corrupt' => UploadedFile::fake()->createWithContent('broken.png', 'not pixels'),
        'dimensions' => UploadedFile::fake()->image('wide.png', 4100, 1),
        'size' => UploadedFile::fake()->image('large.png')->size(5121),
        default => UploadedFile::fake()->image('poster.png'),
    };

    // Act
    $response = put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => $kind === 'alt' ? '' : 'Event poster']);

    // Assert
    $response->assertSessionHasErrors($kind === 'alt' ? 'poster_alt' : 'poster');

    expect(EventPublication::query()->count())->toBe(0);
})->with(['svg', 'corrupt', 'dimensions', 'size', 'alt']);

test('removing a draft poster leaves the released poster intact until reviewed', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event, ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster']);
    $publication = releasePublicPage($event);
    savePublicDraft($event, ['remove_poster' => 1]);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response->assertSee('Event poster');

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response->assertNotFound();
    releasePublicPage($event);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response->assertDontSee('Event poster');
});

test('an unselected multipart file input does not prevent saving a text only draft', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];

    $publication = savePublicDraft($event, ['poster' => UploadedFile::fake()->createWithContent('', '')]);

    expect($publication->draft['poster'])->toBeNull();
});

test('a stale poster upload does not leave an unreferenced derivative or replace the approved file', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $editor = get(route('events.public-pages.edit', $event));
    savePublicDraft($event, ['poster' => UploadedFile::fake()->image('approved.png'), 'poster_alt' => 'Approved poster']);
    $files = Storage::disk('local')->allFiles('event-posters');

    // Act
    $response = put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'poster' => UploadedFile::fake()->image('stale.png'), 'poster_alt' => 'Stale poster']);

    // Assert
    $response->assertSessionHasErrors('publication');

    expect(Storage::disk('local')->allFiles('event-posters'))->toBe($files);
});

test('a persistence failure discards only this request derivative and propagates the failure', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $source = app(PublicEventSourceQuery::class)->get($event);
    $save = Double::for(SaveEventPublicationAction::class);
    $save->expects('handle')->throws(new RuntimeException('Persistence unavailable'));
    app()->instance(SaveEventPublicationAction::class, $save);

    // Act
    $response = put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => UploadedFile::fake()->image('pending.png'), 'poster_alt' => 'Event poster']);

    // Assert
    $response->assertServerError();

    expect(Storage::disk('local')->allFiles('event-posters'))->toBeEmpty();
    $save->verify();
});
