<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

/** @return array{event: Event, match: EventMatch, actor: User, left: Wrestler, right: Wrestler} */
function publicEventFixture(): array
{
    app(PromotionContextService::class)->clear();
    $actor = administrator();
    actingAs($actor);
    $promotion = Promotion::factory()->create(['name' => 'Harbor Wrestling', 'timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->past()->create(['name' => 'Harbor Night', 'preview' => 'LEAK-EVENT-7Q']);
    $left = Wrestler::factory()->for($promotion)->bookable()->create(['name' => 'Robin North']);
    $right = Wrestler::factory()->for($promotion)->bookable()->create(['name' => 'Casey Shore']);
    $match = EventMatch::factory()->for($event)->withCompetitors([$left, $right])->create(['preview' => 'LEAK-MATCH-7Q']);
    $match->forceFill(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $match->sides()->firstOrFail()->id])->save();

    return ['event' => $event, 'match' => $match, 'actor' => $actor, 'left' => $left, 'right' => $right];
}

/** @param array<string, mixed> $changes */
function savePublicDraft(Event $event, array $changes = []): EventPublication
{
    $editor = get(route('events.public-pages.edit', $event));
    $editor->assertOk();
    $match = $event->matches()->first();
    $payload = ['complete' => 1, 'version' => EventPublication::query()->where('event_id', $event->id)->first()->version ?? 0,
        'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'announcement' => 'scheduled',
        'summary' => 'Welcome to Harbor Night.', 'matches' => $match === null ? [] : [['id' => $match->id, 'selected' => 1, 'type' => 1]],
        'results' => $match === null ? [] : [$match->id]];

    put(route('events.public-pages.update', $event), array_replace($payload, $changes))->assertSessionHasNoErrors();

    return EventPublication::query()->where('event_id', $event->id)->firstOrFail();
}

function releasePublicPage(Event $event, string $section = 'card'): EventPublication
{
    $editor = get(route('events.public-pages.edit', $event));
    post(route('events.public-pages.publish', $event), ['complete' => 1, 'version' => EventPublication::query()->where('event_id', $event->id)->firstOrFail()->version,
        'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'section' => $section, 'reviewed' => 1])->assertSessionHasNoErrors();

    return EventPublication::query()->where('event_id', $event->id)->firstOrFail();
}

/**
 * An event with a released card (and released results when asked).
 *
 * @return array{world: array{event: Event, match: EventMatch, actor: User, left: Wrestler, right: Wrestler}, publication: EventPublication}
 */
function releasedPublicationWorld(bool $withResults = false): array
{
    $world = publicEventFixture();
    savePublicDraft($world['event']);
    $publication = releasePublicPage($world['event']);
    if ($withResults) {
        $publication = releasePublicPage($world['event'], 'results');
    }

    return ['world' => $world, 'publication' => $publication];
}

function publicShowUrl(EventPublication $publication, bool $results = false): string
{
    return route($results ? 'shows.results' : 'shows.show', ['publicId' => $publication->public_id, 'slug' => $publication->slug]);
}

/** Each channel must return its expected content, so a missing route cannot masquerade as privacy.
 * @return array<string, string>
 */
function publicEventSurfaces(Event $event, EventPublication $publication): array
{
    $preview = get(route('events.public-pages.show', $event))->assertOk()->assertSee('Harbor Night');
    auth()->logout();
    app(PromotionContextService::class)->clear();
    $card = get(publicShowUrl($publication))->assertOk()->assertSee('Harbor Night');
    $results = get(publicShowUrl($publication, true))->assertOk()->assertSee('Selected results');

    return ['preview' => (string) $preview->getContent(), 'card' => (string) $card->getContent(), 'results' => (string) $results->getContent()];
}

function publicPosterKey(EventPublication $publication): string
{
    $poster = $publication->draft['poster'] ?? throw new RuntimeException('Expected draft poster');

    return $poster['key'];
}
