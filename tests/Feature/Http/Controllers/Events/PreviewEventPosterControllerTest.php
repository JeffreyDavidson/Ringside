<?php

declare(strict_types=1);

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\StoreEventPosterAction;
use App\Enums\Events\PublicSection;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\EventPublication;
use App\Models\Promotions\Promotion;
use App\Services\Events\PublicEventSourceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

test('the private preview streams the draft without publishing it', function (): void {
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
    $bytes = Storage::disk('local')
        ->get('event-posters/'.$key) ?? throw new RuntimeException('Expected poster bytes');

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertOk()
        ->assertStreamedContent($bytes)
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect($publication->refresh()->published_at)->toBeNull();
});

test('a private preview without a draft is not found', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertNotFound();
});

test('a private preview without a poster is not found', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertNotFound();
});

test('a private preview with a missing file is not found', function (): void {
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
    Storage::disk('local')
        ->delete('event-posters/'.$key);

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertNotFound();
});

test('guest cannot preview the private poster or change publication', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $original = $publication->getAttributes();
    auth()->logout();
    session()->flush();

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('member cannot preview the private poster or change publication', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $original = $publication->getAttributes();
    auth()->logout();
    session()->flush();
    actingAsPromotionMember($event->promotion()
        ->firstOrFail(), MembershipRole::Member);

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertForbidden();

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('outsider cannot preview the private poster or change publication', function (): void {
    // Arrange
    Storage::fake('local');
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $original = $publication->getAttributes();
    auth()->logout();
    session()->flush();
    $foreign = Promotion::factory()
        ->create();
    actingAsPromotionMember($foreign, MembershipRole::Owner);

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertNotFound();

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});
