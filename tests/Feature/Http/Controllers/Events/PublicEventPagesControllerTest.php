<?php

declare(strict_types=1);

use App\Data\Events\PublicEventData;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

test('a saved public draft remains private until explicitly reviewed and published', function (): void {
    $event = Event::factory()->for(Promotion::factory())->scheduled()->create(['name' => 'Harbor Night', 'preview' => 'LEAK-EVENT-NOTE-7Q']);
    actingAs(administrator());
    $editor = get(route('events.public-pages.edit', $event));
    $editor->assertOk();

    $response = put(route('events.public-pages.update', $event), [
        'complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'),
        'announcement' => 'scheduled', 'summary' => 'A night at the harbor.', 'matches' => [],
    ]);

    $response->assertSessionHasNoErrors();
    $publication = EventPublication::query()->where('event_id', $event->id)->firstOrFail();
    expect($publication->card)->toBeNull();
    get(route('shows.show', ['publicId' => $publication->public_id, 'slug' => $publication->slug]))->assertNotFound();
    $preview = get(route('events.public-pages.show', $event));
    $preview->assertSee('Harbor Night')->assertDontSee('LEAK-EVENT-NOTE-7Q')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    post(route('events.public-pages.publish', $event), ['complete' => 1, 'version' => 1, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'reviewed' => 1, 'section' => 'card'])->assertSessionHasNoErrors();
    auth()->logout();
    get(route('shows.show', ['publicId' => $publication->public_id, 'slug' => $publication->slug]))->assertSee('A night at the harbor.')->assertDontSee('LEAK-EVENT-NOTE-7Q');
});

test('members cannot prepare public pages while owners and managers can', function (MembershipRole $role, bool $allowed): void {
    $event = Event::factory()->for(Promotion::factory())->scheduled()->create();
    actingAsPromotionMember($event->promotion()->firstOrFail(), $role);

    $response = get(route('events.public-pages.edit', $event));

    if ($allowed) {
        $response->assertOk();

        return;
    }
    $response->assertForbidden();
})->with([[MembershipRole::Owner, true], [MembershipRole::Manager, true], [MembershipRole::Member, false]]);

test('publication input rejects ticket schemes private hosts credentials and malformed urls', function (string $url): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $editor = get(route('events.public-pages.edit', $event));

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'tickets' => $url])->assertSessionHasErrors('tickets')->assertSessionHasInput('tickets', $url);

    expect(EventPublication::query()->count())->toBe(0);
})->with(['javascript:alert(1)', 'http://tickets.example.com', '//tickets.example.com', 'https://localhost/show', 'https://127.0.0.1/show', 'https://192.168.1.2/show', 'https://[::1]/show', 'https://user:secret@tickets.example.com', 'https://arena.local/show']);

test('doors time requires an explicit valid offset through daylight saving transitions', function (string $local, string $offset, bool $valid): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $editor = get(route('events.public-pages.edit', $event));

    $response = put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'doors_at' => $local, 'doors_offset' => $offset]);

    if ($valid) {
        $response->assertSessionHasNoErrors();

        return;
    }
    $response->assertSessionHasErrors('doors_at');
    expect(EventPublication::query()->count())->toBe(0);
})->with([
    ['not a date', '-04:00', false],
    ['2026-03-08T02:30', '-05:00', false], ['2026-11-01T01:30', '', false],
    ['2026-11-01T01:30', '-04:00', true], ['2026-11-01T01:30', '-05:00', true],
    ['2026-11-01T01:30', '+00:00', false], ['2026-10-10T19:00', '-04:00', true],
]);

test('foreign match and result selections are rejected without changing existing draft', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event);
    $foreign = EventMatch::factory()->create();
    $editor = get(route('events.public-pages.edit', $event));

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => $publication->version, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled', 'matches' => [['id' => $foreign->id, 'selected' => 1]], 'results' => [$foreign->id]])->assertSessionHasErrors(['matches.0.id', 'results.0']);

    expect(freshModel($publication)->draft)->toBe($publication->draft);
});

test('a stale source cannot save and a release requires deliberate review', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $editor = get(route('events.public-pages.edit', $event));
    $event->update(['name' => 'Changed private event name']);

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled'])->assertSessionHasErrors('publication');
    expect(EventPublication::query()->count())->toBe(0);
    $publication = savePublicDraft($event);
    $source = app(PublicEventSourceQuery::class)->get($event);
    post(route('events.public-pages.publish', $event), ['complete' => 1, 'version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card'])->assertSessionHasErrors('reviewed');
    expect(freshModel($publication)->card)->toBeNull();
});

test('public projections expose only the explicitly permitted page and row properties', function (): void {
    $world = publicEventFixture();
    savePublicDraft($world['event']);
    $publication = releasePublicPage($world['event']);

    $response = get(publicShowUrl($publication));
    $page = $response->viewData('page');

    if (! $page instanceof PublicEventData) {
        throw new RuntimeException('Public projection missing');
    }
    expect(array_keys(get_object_vars($page)))->toBe(['title', 'promotion', 'announcement', 'summary', 'notice', 'date', 'timezone', 'venue', 'address', 'doors', 'tickets', 'posterUrl', 'posterAlt', 'posterCaption', 'rows', 'cardUrl', 'resultsUrl', 'isResults', 'isPreview', 'scheduleChanged', 'resultsChanged', 'correctedAt'])
        ->and(array_keys($page->rows[0]))->toBe(['label', 'details', 'outcome']);
    $response->assertDontSee('LEAK-EVENT-7Q')->assertDontSee('LEAK-MATCH-7Q')->assertDontSee('event_id')->assertDontSee('winning_side_id');
});

test('a malformed offset payload receives validation errors rather than a server error', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $source = app(PublicEventSourceQuery::class)->get($event);

    put(route('events.public-pages.update', $event), ['complete' => 1, 'version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'doors_at' => '2026-10-10T19:00', 'doors_offset' => ['malformed']])->assertSessionHasErrors(['doors_offset', 'doors_at']);
});

test('a truncated multipart form cannot silently save fewer announced matches', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    $source = app(PublicEventSourceQuery::class)->get($event);

    put(route('events.public-pages.update', $event), ['version' => 0, 'fingerprint' => $source['fingerprint'], 'announcement' => 'scheduled', 'matches' => [['id' => $world['match']->id, 'selected' => 1]]])->assertSessionHasErrors('complete');

    expect(EventPublication::query()->count())->toBe(0);
});

test('stale publish and unpublish forms retain input and leave the newer release intact', function (string $operation): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $draft = savePublicDraft($event);
    $source = app(PublicEventSourceQuery::class)->get($event);
    $released = releasePublicPage($event);
    $payload = ['version' => $draft->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card', 'reviewed' => 1];

    // Act
    $response = $operation === 'publish'
        ? post(route('events.public-pages.publish', $event), $payload)
        : delete(route('events.public-pages.unpublish', $event), $payload);

    // Assert
    $response->assertSessionHasErrors('publication')->assertSessionHasInput('version', $draft->version);
    expect(freshModel($released)->published_at)->not->toBeNull()->and(freshModel($released)->version)->toBe($released->version);
})->with(['publish', 'unpublish']);
