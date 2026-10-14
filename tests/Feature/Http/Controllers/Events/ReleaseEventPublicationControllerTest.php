<?php

declare(strict_types=1);

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Enums\Events\PublicSection;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\EventPublication;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\from;
use function Pest\Laravel\post;

test('an authorized publish request updates the release and returns to its editor', function (): void {
    // Arrange
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
    $source = app(PublicEventSourceQuery::class)->get($event);

    $requestData = [
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ];

    // Act
    $response = post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.published'));

    $savedPublication = freshModel($publication);
    expect($savedPublication->version)->toBe($publication->version + 1)
        ->and($savedPublication->published_at)->not->toBeNull()
        ->and($savedPublication->published_by)
        ->toBe($fixture['actor']->id)
        ->and(data_get($savedPublication->card, 'summary'))
        ->toBe('Welcome to Harbor Night.');
});

test('a stale publish request preserves the newer release and submitted input', function (): void {
    // Arrange
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
    $staleVersion = $publication->version;
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $released = $publication;
    $source = app(PublicEventSourceQuery::class)->get($event);

    $requestData = [
        'version' => $staleVersion,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ];

    // Act
    $response = from(route('events.public-pages.edit', $event))->post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasErrors('publication')
        ->assertSessionHasInput('version', $staleVersion)
        ->assertSessionHasInput('section', 'card');

    expect(freshModel($released)->getAttributes())->toBe($released->getAttributes());
});

test('a publish request requires deliberate review', function (): void {
    // Arrange
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
    $source = app(PublicEventSourceQuery::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card'];

    // Act
    $response = post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertSessionHasErrors('reviewed');

    expect($publication->refresh()->card)->toBeNull();
});

test('reviewed results are released separately without replacing the card', function (): void {
    // Arrange
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
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $beforeMatch = DB::table('events_matches')
        ->where('event_id', $event->id)
        ->first();
    $card = $publication->card;
    $version = $publication->version;
    $requestData = ['version' => $version, 'fingerprint' => $source['fingerprint'], 'section' => 'results', 'reviewed' => 1];

    // Act
    $response = post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.published'));

    expect($publication->refresh()->version)->toBe($version + 1)
        ->and($publication->results_published_at)->not->toBeNull()
        ->and($publication->results_published_by)
        ->toBe($fixture['actor']->id)
        ->and(data_get($publication->results, 'rows.0.outcome'))
        ->toBe('Robin North — Pinfall')
        ->and($publication->card)
        ->toBe($card)
        ->and(DB::table('events_matches')
            ->where('event_id', $event->id)
            ->first())
        ->toEqual($beforeMatch);
});

test('guest cannot publish a private publication', function (): void {
    // Arrange
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
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->refresh();
    $original = $publication->getAttributes();
    $source = app(PublicEventSourceQuery::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

    auth()->logout();
    session()->flush();

    // Act
    $response = post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('login'));

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('member cannot publish a private publication', function (): void {
    // Arrange
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
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->refresh();
    $original = $publication->getAttributes();
    $source = app(PublicEventSourceQuery::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

    auth()->logout();
    session()->flush();
    actingAsPromotionMember($event->promotion()
        ->firstOrFail(), MembershipRole::Member);

    // Act
    $response = post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertForbidden();

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('outsider cannot publish a private publication', function (): void {
    // Arrange
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
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->refresh();
    $original = $publication->getAttributes();
    $source = app(PublicEventSourceQuery::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

    auth()->logout();
    session()->flush();
    $foreignPromotion = Promotion::factory()
        ->create();
    actingAsPromotionMember($foreignPromotion, MembershipRole::Owner);

    // Act
    $response = post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertNotFound();

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('invalid release controls preserve the existing draft', function (string $field, mixed $value): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create()
        ->refresh();
    $original = $publication->getRawOriginal();
    $source = app(PublicEventSourceQuery::class)->get($event);

    $requestData = [
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1, $field => $value,
    ];

    // Act
    $response = from(route('events.public-pages.edit', $event))
        ->post(route('events.public-pages.publish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasErrors($field)
        ->assertSessionMissing('success');

    expect($publication->refresh()->getRawOriginal())->toBe($original);
})->with([
    'zero version' => ['version', 0],
    'short fingerprint' => ['fingerprint', 'short'],
    'unknown section' => ['section', 'unknown'],
    'unaccepted review' => ['reviewed', '0'],
]);
