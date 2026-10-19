<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use App\Services\Events\PublicEventCardService;
use App\Services\Events\PublicEventSourceService;
use App\Services\Events\PublishedEventChangesService;

/**
 * Releases the card and results of the shared public event fixture the way a release stores them.
 *
 * @param  array{event: Event, match: EventMatch, actor: User, left: Wrestler, right: Wrestler}  $fixture
 */
function releasedPublication(array $fixture): EventPublication
{
    $source = resolve(PublicEventSourceService::class)->get($fixture['event']);
    $cards = resolve(PublicEventCardService::class);
    $selection = [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]];

    return EventPublication::factory()
        ->for($fixture['event'])
        ->create([
            'published_at' => now(),
            'card_source' => $selection,
            'results_source' => [$fixture['match']->id],
            'schedule_fingerprint' => $source['schedule'],
            'card_fingerprint' => $cards->cardFingerprint($source, $selection),
            'results_fingerprint' => $cards->fingerprint($cards->resultSource($source, [$fixture['match']->id])),
        ]);
}

describe('published event changes service', function (): void {
    it('reports no change for an event that is as released', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = releasedPublication($fixture);

        // Act
        $changes = resolve(PublishedEventChangesService::class)->get($publication);

        // Assert
        expect($changes)->toBe(['scheduleChanged' => false, 'cardChanged' => false, 'resultsChanged' => false]);
    });

    it('reports no change when the venue of the released publication is deleted afterwards', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $venue = Venue::factory()
            ->create(['name' => 'Harbor Arena']);
        $fixture['event']->update(['venue_id' => $venue->id]);
        $publication = releasedPublication($fixture);
        $venue->delete();

        // Act
        $changes = resolve(PublishedEventChangesService::class)->get($publication->refresh());

        // Assert
        expect($changes)->toBe(['scheduleChanged' => false, 'cardChanged' => false, 'resultsChanged' => false]);
    });

    it('reports a changed schedule when the date moves', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = releasedPublication($fixture);
        $fixture['event']->update(['date' => now()->addDays(10)]);

        // Act
        $changes = resolve(PublishedEventChangesService::class)->get($publication->refresh());

        // Assert
        expect($changes)->toBe(['scheduleChanged' => true, 'cardChanged' => false, 'resultsChanged' => false]);
    });

    it('reports a changed card when a competitor is renamed', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = releasedPublication($fixture);
        $fixture['left']->update(['name' => 'Robin Renamed']);

        // Act
        $changes = resolve(PublishedEventChangesService::class)->get($publication->refresh());

        // Assert
        expect($changes['cardChanged'])->toBeTrue()
            ->and($changes['scheduleChanged'])->toBeFalse();
    });

    it('reports changed results when a finish is corrected', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = releasedPublication($fixture);
        $fixture['match']->forceFill(['match_finish' => MatchFinish::Submission])->save();

        // Act
        $changes = resolve(PublishedEventChangesService::class)->get($publication->refresh());

        // Assert
        expect($changes['resultsChanged'])->toBeTrue()
            ->and($changes['scheduleChanged'])->toBeFalse();
    });

    it('compares against a source the caller already read without reading again', function (): void {
        // Arrange
        $fixture = publicEventFixture();
        $publication = releasedPublication($fixture);
        $source = resolve(PublicEventSourceService::class)->get($fixture['event']);
        $service = resolve(PublishedEventChangesService::class);

        // Act
        $statements = recordStatements(fn () => $service->compare($publication, $source));

        // Assert
        expect($statements)->toBeEmpty()
            ->and($service->compare($publication, $source))->toBe(['scheduleChanged' => false, 'cardChanged' => false, 'resultsChanged' => false]);
    });
});
