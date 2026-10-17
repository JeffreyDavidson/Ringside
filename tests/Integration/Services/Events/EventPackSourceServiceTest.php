<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\EventPackCardService;
use App\Services\Events\EventPackSourceService;

/** @return array{event: Event, first: EventMatch, second: EventMatch} */
function packSourceFixture(): array
{
    $promotion = Promotion::factory()
        ->create(['timezone' => 'America/New_York']);
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    [$alpha, $bravo, $charlie] = Wrestler::factory()
        ->count(3)
        ->sequence(['name' => 'Alpha'], ['name' => 'Bravo'], ['name' => 'Charlie'])
        ->bookable()
        ->for($promotion)
        ->create()
        ->all();
    $first = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$alpha, $bravo])
        ->create();
    $second = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$bravo, $charlie])
        ->create();

    return ['event' => $event, 'first' => $first, 'second' => $second];
}

/** @param array<string, mixed> $content */
function savePackSourceFixturePack(Event $event, string $fingerprint, array $content): EventPack
{
    return EventPack::factory()
        ->for($event)
        ->create(['card_fingerprint' => $fingerprint, 'content' => $content]);
}

test('without a saved pack it offers the card as the running order with an empty pack', function (): void {
    // Arrange
    $fixture = packSourceFixture();
    $card = resolve(EventPackCardService::class)->get($fixture['event']);

    // Act
    $source = resolve(EventPackSourceService::class)->get($fixture['event']);

    // Assert
    expect($source['pack'])->toBeNull()
        ->and($source['needsReview'])->toBeFalse()
        ->and($source['card'])->toBe($card)
        ->and($source['content'])->toBe(['timezone' => 'America/New_York', 'doors_at' => null, 'revision' => null, 'master' => null, 'notes' => null, 'follow_up' => null, 'matches' => [], 'segments' => []])
        ->and(array_column($source['running'], 'key'))->toBe(["match:{$fixture['first']->id}", "match:{$fixture['second']->id}"])
        ->and($source['running'][0])->toBe(['key' => "match:{$fixture['first']->id}", 'label' => '1. Alpha vs Bravo', 'details' => $card['rows'][0]['details'], 'referees' => '', 'warning' => false, 'timing' => []])
        ->and($source['removed'])->toBeEmpty()
        ->and($source['unplaced'])->toBeEmpty();
});

test('places segments around the matches they follow and keeps notes for matches that left the card', function (): void {
    // Arrange
    $fixture = packSourceFixture();
    $card = resolve(EventPackCardService::class)->get($fixture['event']);
    $opening = ['id' => 'seg-open', 'label' => 'Opening video', 'after_match_id' => null, 'planned_at' => null, 'revised_at' => null, 'minutes' => 5, 'notes' => null];
    $intermission = ['id' => 'seg-mid', 'label' => 'Intermission', 'after_match_id' => $fixture['first']->id, 'planned_at' => null, 'revised_at' => null, 'minutes' => 10, 'notes' => null];
    $orphan = ['id' => 'seg-gone', 'label' => 'After a removed match', 'after_match_id' => 999999, 'planned_at' => null, 'revised_at' => null, 'minutes' => null, 'notes' => null];
    $timing = ['match_id' => $fixture['second']->id, 'planned_at' => '2026-10-24T20:00:00-04:00', 'revised_at' => null, 'minutes' => 15, 'notes' => 'Main event'];
    $removed = ['match_id' => 888888, 'planned_at' => null, 'revised_at' => null, 'minutes' => 7, 'notes' => 'Cut from the card'];
    savePackSourceFixturePack($fixture['event'], $card['fingerprint'], ['timezone' => 'America/New_York', 'doors_at' => null, 'revision' => 'B', 'master' => null,
        'notes' => null, 'follow_up' => null, 'matches' => [$timing, $removed], 'segments' => [$opening, $intermission, $orphan]]);

    // Act
    $source = resolve(EventPackSourceService::class)->get($fixture['event']);

    // Assert
    expect(array_column($source['running'], 'key'))->toBe(['segment:seg-open', "match:{$fixture['first']->id}", 'segment:seg-mid', "match:{$fixture['second']->id}"])
        ->and($source['running'][0])->toBe(['key' => 'segment:seg-open', 'label' => 'Opening video', 'details' => '', 'referees' => '', 'warning' => false, 'timing' => $opening])
        ->and($source['running'][3]['timing'])->toBe($timing)
        ->and($source['removed'])->toBe([$removed])
        ->and($source['unplaced'])->toBe([$orphan])
        ->and($source['needsReview'])->toBeFalse();
});

test('asks for a review when the card no longer matches the fingerprint the pack was saved with', function (): void {
    // Arrange
    $fixture = packSourceFixture();
    $card = resolve(EventPackCardService::class)->get($fixture['event']);
    $pack = savePackSourceFixturePack($fixture['event'], $card['fingerprint'], ['timezone' => 'UTC', 'doors_at' => null, 'revision' => null, 'master' => null,
        'notes' => null, 'follow_up' => null, 'matches' => [], 'segments' => []]);
    $service = resolve(EventPackSourceService::class);
    $matching = $service->get($fixture['event']);
    $fixture['second']->delete();

    // Act
    $moved = $service->get($fixture['event']);

    // Assert
    expect($matching['needsReview'])->toBeFalse()
        ->and($matching['pack']?->is($pack))->toBeTrue()
        ->and($moved['needsReview'])->toBeTrue()
        ->and($moved['pack']?->card_fingerprint)->toBe($card['fingerprint'])
        ->and($moved['card']['fingerprint'])->not->toBe($card['fingerprint']);
});
