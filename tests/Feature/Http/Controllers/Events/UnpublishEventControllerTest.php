<?php

declare(strict_types=1);

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Enums\Events\PublicSection;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\EventPublication;
use App\Models\Promotions\Promotion;
use App\Services\Events\PublicEventSourceService;

use function Pest\Laravel\delete;
use function Pest\Laravel\from;

test('an authorized unpublish request updates the release and returns to its editor', function (): void {
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
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $source = app(PublicEventSourceService::class)->get($event);

    $requestData = [
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ];

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.unpublished'));

    $savedPublication = freshModel($publication);
    expect($savedPublication->version)->toBe($publication->version + 1)
        ->and($savedPublication->published_at)
        ->toBeNull();
});

test('a stale unpublish request preserves the newer release and submitted input', function (): void {
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
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $released = $publication;
    $source = app(PublicEventSourceService::class)->get($event);

    $requestData = [
        'version' => $staleVersion,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ];

    // Act
    $response = from(route('events.public-pages.edit', $event))->delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasErrors(['publication' => 'This was changed by someone else. Reload to see the latest version, review it, then try again.'])
        ->assertSessionHasInput('version', $staleVersion)
        ->assertSessionHasInput('section', 'card');

    expect(freshModel($released)->getAttributes())->toBe($released->getAttributes());
});

test('unpublishing results preserves the separately released card', function (): void {
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
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    $card = $publication->card;
    $version = $publication->version;
    $publishedAt = $publication->published_at;
    $requestData = ['version' => $version, 'fingerprint' => $source['fingerprint'], 'section' => 'results'];

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.unpublished'));

    expect($publication->refresh()->version)->toBe($version + 1)
        ->and($publication->results_published_at)
        ->toBeNull()
        ->and($publication->published_at)
        ->toEqual($publishedAt)
        ->and($publication->card)
        ->toBe($card);
});

test('guest cannot unpublish a private publication', function (): void {
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
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->refresh();
    $original = $publication->getAttributes();
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

    auth()->logout();
    session()->flush();

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('login'));

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('member cannot unpublish a private publication', function (): void {
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
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->refresh();
    $original = $publication->getAttributes();
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

    auth()->logout();
    session()->flush();
    actingAsPromotionMember($event->promotion()
        ->firstOrFail(), MembershipRole::Member);

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertForbidden();

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('outsider cannot unpublish a private publication', function (): void {
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
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $publication->refresh();
    $original = $publication->getAttributes();
    $source = app(PublicEventSourceService::class)->get($event);
    $requestData = ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'summary' => 'Do not save', 'section' => 'card', 'reviewed' => 1];

    auth()->logout();
    session()->flush();
    $foreignPromotion = Promotion::factory()
        ->create();
    actingAsPromotionMember($foreignPromotion, MembershipRole::Owner);

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertNotFound();

    expect($publication->refresh()
        ->getAttributes())->toBe($original);
});

test('invalid unpublish controls preserve the released card', function (string $field, mixed $value): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = EventPublication::factory()
        ->for($event)
        ->create();
    $publication->draft = [
        ...$publication->draft,
        'matches' => [
            [
                'id' => $fixture['match']->id,
                'type' => true,
                'stipulation' => false,
                'titles' => false,
            ],
        ],
    ];
    $publication->save();
    $source = app(PublicEventSourceService::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    $original = $publication->getRawOriginal();
    $source = app(PublicEventSourceService::class)->get($event);

    $requestData = [
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card', $field => $value,
    ];

    // Act
    $response = from(route('events.public-pages.edit', $event))
        ->delete(route('events.public-pages.unpublish', $event), $requestData);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasErrors($field)
        ->assertSessionHasInput($field, $value)
        ->assertSessionMissing('success');

    expect($publication->refresh()->getRawOriginal())->toBe($original);
})->with([
    'zero version' => ['version', 0],
    'short fingerprint' => ['fingerprint', 'short'],
    'unknown section' => ['section', 'unknown'],
    'nonboolean review' => ['reviewed', 'yes'],
]);
