<?php

declare(strict_types=1);

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\StoreEventPosterAction;
use App\Enums\Events\PublicSection;
use App\Models\Events\EventPublication;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

test('the released poster streams approved bytes with private security headers', function (): void {
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
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Approved poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $bytes = Storage::disk('local')
        ->get('event-posters/'.$key) ?? throw new RuntimeException('Expected poster bytes');
    auth()->logout();

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertOk()
        ->assertStreamedContent($bytes)
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('a missing file cannot expose a public poster', function (): void {
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
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Approved poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    Storage::disk('local')
        ->delete('event-posters/'.$key);
    auth()->logout();

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});

test('an unapproved key cannot expose a public poster', function (): void {
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
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Approved poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $key = '00000000-0000-4000-8000-000000000000.png';
    auth()->logout();

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});

test('the wrong slug cannot expose a public poster', function (): void {
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
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => ['key' => $key, 'alt' => 'Approved poster', 'caption' => null],
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->slug = 'wrong-slug';
    auth()->logout();

    // Act
    $response = get(route('shows.poster', ['publicId' => $publication->public_id, 'slug' => $publication->slug, 'asset' => $key]));

    // Assert
    $response
        ->assertNotFound();
});
