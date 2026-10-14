<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;

use function Pest\Laravel\from;
use function Pest\Laravel\post;

test('an authorized publish request updates the release and returns to its editor', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $publication = savePublicDraft($event);
    $source = app(PublicEventSourceQuery::class)->get($event);

    // Act
    $response = post(route('events.public-pages.publish', $event), [
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ]);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.published'));

    $savedPublication = freshModel($publication);
    expect($savedPublication->version)->toBe($publication->version + 1)
        ->and($savedPublication->published_at)->not->toBeNull()
        ->and($savedPublication->published_by)->toBe($fixture['actor']->id)
        ->and(data_get($savedPublication->card, 'summary'))->toBe('Welcome to Harbor Night.');
});

test('a stale publish request preserves the newer release and submitted input', function (): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $draft = savePublicDraft($event);
    $released = releasePublicPage($event);
    $source = app(PublicEventSourceQuery::class)->get($event);

    // Act
    $response = from(route('events.public-pages.edit', $event))->post(route('events.public-pages.publish', $event), [
        'version' => $draft->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ]);

    // Assert
    $response
        ->assertRedirect(route('events.public-pages.edit', $event))
        ->assertSessionHasErrors('publication')
        ->assertSessionHasInput('version', $draft->version)
        ->assertSessionHasInput('section', 'card');

    expect(freshModel($released)->getAttributes())->toBe($released->getAttributes());
});

test('private publish refuses guests members and outsiders without changing publication', function (string $actor): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    $source = app(PublicEventSourceQuery::class)->get($event);
    auth()->logout();
    session()->flush();
    if ($actor !== 'guest') {
        $actorPromotion = $actor === 'outsider' ? Promotion::factory()->create() : $event->promotion()->firstOrFail();
        actingAsPromotionMember($actorPromotion, $actor === 'outsider' ? MembershipRole::Owner : MembershipRole::Member);
    }

    // Act
    $response = post(route('events.public-pages.publish', $event), [
        'version' => $publication->version,
        'fingerprint' => $source['fingerprint'],
        'section' => 'card',
        'reviewed' => 1,
    ]);

    // Assert
    if ($actor === 'guest') {
        $response->assertRedirect(route('login'));
    } elseif ($actor === 'outsider') {
        $response->assertNotFound();
    } else {
        $response->assertForbidden();
    }

    expect(freshModel($publication)->getAttributes())->toBe($publication->getAttributes());
})->with([
    'guest' => 'guest',
    'member' => 'member',
    'outsider' => 'outsider',
]);
