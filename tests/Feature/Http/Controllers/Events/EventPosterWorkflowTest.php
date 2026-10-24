<?php

declare(strict_types=1);
use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\SaveEventPublicationAction;
use App\Actions\Events\StoreEventPosterAction;
use App\Actions\Events\UnpublishEventPublicationAction;
use App\Data\Events\EventPublicationDraftData;
use App\Enums\Events\PublicSection;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Double;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

test('uploading a poster strips metadata and preserves public alternative text', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $image = UploadedFile::fake()
        ->image('private-camera-name.png', 60, 80);
    $original = $image->getContent();
    $marker = 'LEAK-GPS-CONTACT-7Q';
    $chunk = 'tEXt'."Comment\0".$marker;
    $bytes = substr($original, 0, -12).pack('N', strlen($chunk) - 4).$chunk.pack('N', crc32($chunk)).substr($original, -12);
    $upload = UploadedFile::fake()
        ->createWithContent('private-camera-name.png', $bytes);
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors();

    $publication = EventPublication::query()
        ->where('event_id', $event->id)
        ->sole();
    $poster = $publication->draft['poster'];
    Assert::assertNotNull($poster);
    $key = publicPosterKey($publication);
    expect(Storage::disk('local')
        ->get('event-posters/'.$key))->not->toContain($marker)->not->toContain('private-camera-name')
        ->and($poster['alt'])
        ->toBe('Harbor Night event poster')
        ->and($poster['caption'])
        ->toBe('Join us by the harbor.');
});

test('an unreleased draft poster remains unavailable publicly', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});

test('a pending replacement cannot revoke the approved released poster', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $oldKey = $key;
    $newKey = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('replacement.png', 50, 50));
    $publication = app(SaveEventPublicationAction::class)->handle(
        $event,
        $publication->version,
        $source['fingerprint'],
        EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => ['key' => $newKey, 'alt' => 'Replacement event poster', 'caption' => null]]),
    );

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertOk();
});

test('a pending replacement cannot expose its new asset publicly', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $oldKey = $key;
    $newKey = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('replacement.png', 50, 50));
    $publication = app(SaveEventPublicationAction::class)->handle(
        $event,
        $publication->version,
        $source['fingerprint'],
        EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => ['key' => $newKey, 'alt' => 'Replacement event poster', 'caption' => null]]),
    );
    $key = $newKey;

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});

test('a reviewed replacement revokes the previously approved poster key', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $oldKey = $key;
    $newKey = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('replacement.png', 50, 50));
    $publication = app(SaveEventPublicationAction::class)->handle(
        $event,
        $publication->version,
        $source['fingerprint'],
        EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => ['key' => $newKey, 'alt' => 'Replacement event poster', 'caption' => null]]),
    );
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $key = $oldKey;

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});

test('a reviewed replacement serves its new poster key', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $oldKey = $key;
    $newKey = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('replacement.png', 50, 50));
    $publication = app(SaveEventPublicationAction::class)->handle(
        $event,
        $publication->version,
        $source['fingerprint'],
        EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => ['key' => $newKey, 'alt' => 'Replacement event poster', 'caption' => null]]),
    );
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $key = $newKey;

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertOk();
});

test('public pages adopt reviewed replacement metadata without the old asset key', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $oldKey = $key;
    $newKey = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('replacement.png', 50, 50));
    $publication = app(SaveEventPublicationAction::class)->handle(
        $event,
        $publication->version,
        $source['fingerprint'],
        EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => ['key' => $newKey, 'alt' => 'Replacement event poster', 'caption' => null]]),
    );
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertSee('Replacement event poster')
        ->assertDontSee($oldKey);
});

test('unpublishing a replaced card also revokes its current poster', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $oldKey = $key;
    $newKey = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('replacement.png', 50, 50));
    $publication = app(SaveEventPublicationAction::class)->handle(
        $event,
        $publication->version,
        $source['fingerprint'],
        EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => ['key' => $newKey, 'alt' => 'Replacement event poster', 'caption' => null]]),
    );
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(UnpublishEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $key = $newKey;

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});

test('SVG upload cannot create a publication draft or poster asset', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $upload = UploadedFile::fake()
        ->createWithContent('active.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('poster');

    expect(EventPublication::query()
        ->count())->toBe(0)
        ->and(Storage::disk('local')
            ->allFiles('event-posters'))
        ->toBeEmpty();
});

test('corrupt image bytes cannot create a publication draft or poster asset', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $upload = UploadedFile::fake()
        ->createWithContent('broken.png', 'not pixels');
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('poster');

    expect(EventPublication::query()
        ->count())->toBe(0)
        ->and(Storage::disk('local')
            ->allFiles('event-posters'))
        ->toBeEmpty();
});

test('oversized image dimensions cannot create a publication draft or poster asset', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $upload = UploadedFile::fake()
        ->image('wide.png', 4100, 1);
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('poster');

    expect(EventPublication::query()
        ->count())->toBe(0)
        ->and(Storage::disk('local')
            ->allFiles('event-posters'))
        ->toBeEmpty();
});

test('oversized file cannot create a publication draft or poster asset', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $upload = UploadedFile::fake()
        ->image('large.png')
        ->size(5121);
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('poster');

    expect(EventPublication::query()
        ->count())->toBe(0)
        ->and(Storage::disk('local')
            ->allFiles('event-posters'))
        ->toBeEmpty();
});

test('missing alternative text cannot create a publication draft or poster asset', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $upload = UploadedFile::fake()
        ->image('poster.png');
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => '', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('poster_alt');

    expect(EventPublication::query()
        ->count())->toBe(0)
        ->and(Storage::disk('local')
            ->allFiles('event-posters'))
        ->toBeEmpty();
});

test('removing a draft poster cannot change its released public poster', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication = app(SaveEventPublicationAction::class)->handle($event, $publication->version, $source['fingerprint'], EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => null]));

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Event poster');
});

test('a removed draft poster is absent from the private preview', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication = app(SaveEventPublicationAction::class)->handle($event, $publication->version, $source['fingerprint'], EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => null]));

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertNotFound();
});

test('a reviewed removal omits the poster from the public page', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication = app(SaveEventPublicationAction::class)->handle($event, $publication->version, $source['fingerprint'], EventPublicationDraftData::fromValidated([...$publication->draft, 'poster' => null]));
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertOk()
        ->assertDontSee('Event poster');
});

test('an unselected multipart file control does not block a text-only draft', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $upload = UploadedFile::fake()
        ->createWithContent('', '');
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors();

    expect(EventPublication::query()
        ->where('event_id', $event->id)
        ->sole()->draft['poster'])->toBeNull();
});

test('a stale upload preserves approved draft attributes and bytes without an orphan', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $publication->refresh();
    $original = $publication->getAttributes();
    $files = Storage::disk('local')
        ->allFiles('event-posters');
    $approvedBytes = Storage::disk('local')
        ->get('event-posters/'.$key);
    $upload = UploadedFile::fake()
        ->image('stale.png');
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('publication');

    expect(Storage::disk('local')
        ->allFiles('event-posters'))->toBe($files)
        ->and(Storage::disk('local')
            ->get('event-posters/'.$key))
        ->toBe($approvedBytes)
        ->and($publication->refresh()
            ->getAttributes())
        ->toBe($original);
});

test('a persistence failure discards only this request derivative and propagates the failure', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    Storage::disk('local')
        ->put('event-posters/previously-approved.png', 'APPROVED-BYTES-7Q');
    $save = Double::for(SaveEventPublicationAction::class);
    $save->expects('handle')
        ->throws(new RuntimeException('Persistence unavailable'));
    app()->instance(SaveEventPublicationAction::class, $save);
    $upload = UploadedFile::fake()
        ->image('pending.png');
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'poster' => $upload, 'poster_alt' => 'Harbor Night event poster', 'poster_caption' => 'Join us by the harbor.'];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertServerError();

    expect(Storage::disk('local')
        ->allFiles('event-posters'))->toBe(['event-posters/previously-approved.png'])
        ->and(Storage::disk('local')
            ->get('event-posters/previously-approved.png'))
        ->toBe('APPROVED-BYTES-7Q');
    $save->verify();
});

test('removing a poster through the editor saves only the draft change', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $key = app(StoreEventPosterAction::class)->handle(UploadedFile::fake()
        ->image('poster.png'));
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Event poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $releasedCard = $publication->card;
    $version = $publication->version;
    $requestData = [
        'complete' => 1,
        'version' => $version,
        'fingerprint' => $source['fingerprint'],
        'announcement' => 'scheduled',
        'summary' => 'Welcome to Harbor Night.',
        'matches' => [['id' => $fixture['match']->id, 'selected' => 1, 'type' => 1]],
        'results' => [$fixture['match']->id],
        'remove_poster' => 1,
    ];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.saved'));

    $publication->refresh();
    expect($publication->draft['poster'])->toBeNull()
        ->and($publication->version)
        ->toBe($version + 1)
        ->and($publication->card)
        ->toBe($releasedCard)
        ->and(data_get($publication->card, 'poster.key'))
        ->toBe($key)
        ->and(Storage::disk('local')
            ->exists('event-posters/'.$key))
        ->toBeTrue();
});

test('zero alternative text retains existing poster text while zero caption is preserved', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create();
    $publication->draft = [
        ...$publication->draft,
        'poster' => [
            'key' => 'existing-poster-key',
            'alt' => 'Existing alternative text',
            'caption' => 'Existing caption',
        ],
    ];
    $publication->save();
    $source = app(PublicEventSourceService::class)->get($event);

    $requestData = [
        'complete' => 1,
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'announcement' => 'scheduled',
        'poster_alt' => '0',
        'poster_caption' => '0',
        'remove_poster' => '0',
    ];

    // Act
    $response = put(route('events.public-pages.update', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors();

    expect($publication->refresh()->draft['poster'])->toEqual([
        'key' => 'existing-poster-key',
        'alt' => 'Existing alternative text',
        'caption' => '0',
    ])
        ->and($publication->version)
        ->toBe(2)
        ->and($publication->published_at)
        ->toBeNull();
});
