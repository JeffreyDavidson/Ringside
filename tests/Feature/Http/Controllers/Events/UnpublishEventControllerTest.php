<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;

use function Pest\Laravel\delete;
use function Pest\Laravel\from;

test('an authorized unpublish request updates the release and returns to its editor', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    $source = app(PublicEventSourceQuery::class)->get($event);

    // Act
    $response = delete(route('events.public-pages.unpublish', $event), ['version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card', 'reviewed' => 1]);

    // Assert
    $response->assertRedirect(route('events.public-pages.edit', $event))->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('public-events.unpublished'));
    $saved = freshModel($publication);
    expect($saved->version)->toBe($publication->version + 1)
        ->and($saved->published_at)->toBeNull();
});

test('a stale unpublish request preserves the newer release and submitted input', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $draft = savePublicDraft($event);
    $released = releasePublicPage($event);
    $source = app(PublicEventSourceQuery::class)->get($event);

    // Act
    $response = from(route('events.public-pages.edit', $event))->delete(route('events.public-pages.unpublish', $event), ['version' => $draft->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card', 'reviewed' => 1]);

    // Assert
    $response->assertRedirect(route('events.public-pages.edit', $event))->assertSessionHasErrors('publication')
        ->assertSessionHasInput('version', $draft->version)->assertSessionHasInput('section', 'card');
    expect(freshModel($released)->getAttributes())->toBe($released->getAttributes());
});

test('private unpublish refuses guests members and outsiders without changing publication', function (string $actor): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    $source = app(PublicEventSourceQuery::class)->get($event);
    auth()->logout();
    session()->flush();
    if ($actor !== 'guest') {
        $promotion = $actor === 'outsider' ? Promotion::factory()->create() : $event->promotion()->firstOrFail();
        actingAsPromotionMember($promotion, $actor === 'outsider' ? MembershipRole::Owner : MembershipRole::Member);
    }

    // Act
    $response = \Pest\Laravel\delete(route('events.public-pages.unpublish', $event), ['version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card', 'reviewed' => 1]);

    // Assert
    if ($actor === 'guest') {
        $response->assertRedirect(route('login'));
    } elseif ($actor === 'outsider') {
        $response->assertNotFound();
    } else {
        $response->assertForbidden();
    }
    expect(freshModel($publication)->getAttributes())->toBe($publication->getAttributes());
})->with(['guest', 'member', 'outsider']);
