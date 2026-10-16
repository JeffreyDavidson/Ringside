<?php

declare(strict_types=1);

require_once __DIR__.'/../../../Fixtures/live-show.php';

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\LiveShowSourceQuery;

use function Pest\Laravel\actingAs;

describe('live show source query', function (): void {
    it('reads the running order, roster and production of a prepared card', function (): void {
        // Arrange
        $fixture = liveShowFixture(segment: true);

        // Act
        $source = resolve(LiveShowSourceQuery::class)->get($fixture['event']);

        // Assert
        assert($source !== null);
        expect($source['fingerprint'])->toBe($fixture['event']->pack()->firstOrFail()->card_fingerprint)
            ->and(array_column($source['content']['items'], 'key'))->toContain($fixture['key'])
            ->and($source['content']['items'][0]['state'])->toBe('pending')
            ->and(array_column($source['content']['roster'], 'key'))->toContain($fixture['person'])
            ->and(data_get($source, 'content.production'))->toBe(['assignments' => []]);
    });

    it('returns nothing for an event with no pack', function (): void {
        // Arrange
        actingAs(administrator());
        $event = Event::factory()->create(['date' => now()]);
        EventMatch::factory()->for($event)->withCompetitors(Wrestler::factory()->count(2)->bookable()->create()->all())->withReferees()->create();

        // Act
        $source = resolve(LiveShowSourceQuery::class)->get($event);

        // Assert
        expect($source)->toBeNull();
    });

    it('returns nothing for an event with no date', function (): void {
        // Arrange
        $fixture = liveShowFixture();
        $fixture['event']->forceFill(['date' => null])->save();

        // Act
        $source = resolve(LiveShowSourceQuery::class)->get($fixture['event']);

        // Assert
        expect($source)->toBeNull();
    });

    it('returns nothing when the saved pack no longer matches the card', function (): void {
        // Arrange
        $fixture = liveShowFixture();
        EventMatch::query()->where('event_id', $fixture['event']->id)->firstOrFail()->delete();

        // Act
        $source = resolve(LiveShowSourceQuery::class)->get($fixture['event']);

        // Assert
        expect($source)->toBeNull();
    });
});
