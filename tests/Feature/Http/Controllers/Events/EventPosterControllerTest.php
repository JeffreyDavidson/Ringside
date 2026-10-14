<?php

declare(strict_types=1);
use App\Actions\Events\SaveEventPublicationAction;
use App\Actions\Events\StoreEventPosterAction;
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
    get(route('events.public-pages.poster', $event))->assertOk()->assertHeader('Content-Type', 'image/png');
    $url = route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]);
    get($url)->assertNotFound();
    releasePublicPage($event);
    get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    $publication = savePublicDraft($event, ['poster' => UploadedFile::fake()->image('replacement.png', 50, 50), 'poster_alt' => 'Replacement event poster']);
    get($url)->assertOk();
    releasePublicPage($event);
    get($url)->assertNotFound();
    get(publicShowUrl($publication))->assertSee('Replacement event poster')->assertDontSee($key);
    $newUrl = route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => publicPosterKey($publication)]);
    get($newUrl)->assertOk();
    $editor = get(route('events.public-pages.edit', $event));
    delete(route('events.public-pages.unpublish', $event), ['complete' => 1, 'version' => EventPublication::query()->where('event_id', $event->id)->firstOrFail()->version, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'section' => 'card'])->assertSessionHasNoErrors();
    get($newUrl)->assertNotFound();
});

test('invalid uploads and missing alternative text cannot create a draft', function (string $kind): void {
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

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => $kind === 'alt' ? '' : 'Event poster'])->assertSessionHasErrors($kind === 'alt' ? 'poster_alt' : 'poster');

    expect(EventPublication::query()->count())->toBe(0);
})->with(['svg', 'corrupt', 'dimensions', 'size', 'alt']);

test('removing a draft poster leaves the released poster intact until reviewed', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event, ['poster' => UploadedFile::fake()->image('poster.png'), 'poster_alt' => 'Event poster']);
    $publication = releasePublicPage($event);
    savePublicDraft($event, ['remove_poster' => 1]);

    get(publicShowUrl($publication))->assertSee('Event poster');
    get(route('events.public-pages.poster', $event))->assertNotFound();
    releasePublicPage($event);
    get(publicShowUrl($publication))->assertDontSee('Event poster');
});

test('poster processing limits dimensions without cropping the complete image', function (): void {
    $file = UploadedFile::fake()->image('tall.png', 1000, 3000);

    $key = app(StoreEventPosterAction::class)->handle($file);

    $dimensions = getimagesizefromstring(Storage::disk('local')->get('event-posters/'.$key) ?? throw new RuntimeException('Missing sanitized image')) ?: throw new RuntimeException('Invalid sanitized image');
    expect($dimensions[0])->toBe(683)->and($dimensions[1])->toBe(2048);
});

test('an unselected multipart file input does not prevent saving a text only draft', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];

    $publication = savePublicDraft($event, ['poster' => UploadedFile::fake()->createWithContent('', '')]);

    expect($publication->draft['poster'])->toBeNull();
});

test('a stale poster upload does not leave an unreferenced derivative or replace the approved file', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $editor = get(route('events.public-pages.edit', $event));
    savePublicDraft($event, ['poster' => UploadedFile::fake()->image('approved.png'), 'poster_alt' => 'Approved poster']);
    $files = Storage::disk('local')->allFiles('event-posters');

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'poster' => UploadedFile::fake()->image('stale.png'), 'poster_alt' => 'Stale poster'])->assertSessionHasErrors('publication');

    expect(Storage::disk('local')->allFiles('event-posters'))->toBe($files);
});

test('a persistence failure discards only this request derivative and propagates the failure', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $source = app(PublicEventSourceQuery::class)->get($event);
    $save = Double::for(SaveEventPublicationAction::class);
    $save->expects('handle')->throws(new RuntimeException('Persistence unavailable'));
    app()->instance(SaveEventPublicationAction::class, $save);

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => UploadedFile::fake()->image('pending.png'), 'poster_alt' => 'Event poster'])->assertServerError();

    expect(Storage::disk('local')->allFiles('event-posters'))->toBeEmpty();
    $save->verify();
});
