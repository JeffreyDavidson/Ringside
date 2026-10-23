<?php

declare(strict_types=1);

use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;

use function Pest\Laravel\assertDatabaseHas;

function tagTeamMatchSide(EventMatch $match, int $position): MatchSide
{
    return MatchSide::factory()->for($match, 'match')->create(compact('position'));
}

test('it adds a tag team to a match as a polymorphic competitor', function () {
    $match = EventMatch::factory()->create();
    $tagTeam = TagTeam::factory()->bookable()->create();

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, collect([$tagTeam]), 1));

    assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $tagTeam->id,
        'competitor_type' => $tagTeam->getMorphClass(),
        'match_side_id' => $match->sides()->where('position', 1)->firstOrFail()->id,
    ]);
});

test('it rejects match assignment when no tag team is available', function () {
    $match = EventMatch::factory()->create();
    $tagTeams = TagTeam::factory()->retired()->count(2)->create();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, $tagTeams, 1)))
        ->toThrow(EntityNotAvailableException::class, 'Selected tag teams must all be eligible for match assignment.');
});

test('it rejects the entire assignment when any tag team is unavailable', function () {
    $match = EventMatch::factory()->create();
    $availableTagTeam = TagTeam::factory()->bookable()->create();
    $unavailableTagTeam = TagTeam::factory()->retired()->create();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle(
        $lockedMatch,
        collect([$availableTagTeam, $unavailableTagTeam]),
        1,
    )))->toThrow(EntityNotAvailableException::class)
        ->and($match->competitors()->count())->toBe(0)
        ->and($match->sides()->count())->toBe(0);
});

test('it reloads tag teams before checking assignment eligibility', function () {
    $match = EventMatch::factory()->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $staleTagTeam = TagTeam::query()->findOrFail($tagTeam->id);

    $tagTeam->delete();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, collect([$staleTagTeam]), 1)))
        ->toThrow(EntityNotAvailableException::class)
        ->and($match->competitors()->exists())->toBeFalse();
});

test('it rejects a tag team already booked on the event card', function () {
    $event = Event::factory()->scheduled()->create();
    $existingMatch = EventMatch::factory()->forEvent($event)->create();
    $targetMatch = EventMatch::factory()->forEvent($event)->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $existingSide = tagTeamMatchSide($existingMatch, 1);

    $existingMatch->competitors()->create([
        'match_side_id' => $existingSide->id,
        'competitor_id' => $tagTeam->id,
        'competitor_type' => $tagTeam->getMorphClass(),
    ]);

    expect(fn () => assignWithinLockedMatch($targetMatch, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, collect([$tagTeam]), 1)))
        ->toThrow(SchedulingConflictException::class, "Tag team [{$tagTeam->name}] is already booked at this event time.")
        ->and($targetMatch->competitors()->count())->toBe(0);
});

test('it assigns a repeated tag team only once', function () {
    $match = EventMatch::factory()->create();
    $tagTeam = TagTeam::factory()->bookable()->create();

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, collect([$tagTeam, $tagTeam]), 1));

    expect($match->competitors()->count())->toBe(1);
});

test('it books a tag team on a past event dated while they were employed', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->subDays(10)]))->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $tagTeam->employments()->update(['started_at' => now()->subDays(30)]);
    $tagTeam->currentWrestlers->each(fn ($wrestler): int => $wrestler->employments()->update(['started_at' => now()->subDays(30)]));

    // Act
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, collect([$tagTeam]), 1));

    // Assert
    expect($match->competitors()->count())->toBe(1);
});

test('it refuses a tag team on an event dated before they were employed', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->subDays(10)]))->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $tagTeam->employments()->update(['started_at' => now()->subDays(1)]);
    $tagTeam->currentWrestlers->each(fn ($wrestler): int => $wrestler->employments()->update(['started_at' => now()->subDays(1)]));

    // Act
    $book = fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddTagTeamsToMatchAction::class)->handle($lockedMatch, collect([$tagTeam]), 1));

    // Assert
    expect($book)->toThrow(EntityNotAvailableException::class)
        ->and($match->competitors()->count())->toBe(0);
});
