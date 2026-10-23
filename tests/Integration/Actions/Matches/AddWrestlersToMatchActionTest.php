<?php

declare(strict_types=1);

use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\assertDatabaseHas;

function wrestlerMatchSide(EventMatch $match, int $position): MatchSide
{
    return MatchSide::factory()->for($match, 'match')->create(compact('position'));
}

test('it adds a single wrestler to a match', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    $wrestlers = collect([$wrestler]);
    $sideNumber = 1;

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, $wrestlers, $sideNumber));

    // Should create competitor record
    assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler->id,
        'competitor_type' => $wrestler->getMorphClass(),
        'match_side_id' => $match->sides()->where('position', $sideNumber)->firstOrFail()->id,
    ]);

    // Match should have the wrestler as competitor
    expect($match->refresh()->competitors()->count())->toBe(1);
    expect($match->refresh()->competitors()->firstOrFail()->competitor_id)->toBe($wrestler->id);
});

test('it adds multiple wrestlers to the same side', function () {
    $match = EventMatch::factory()->create();
    $wrestler1 = Wrestler::factory()->employed()->create();
    $wrestler2 = Wrestler::factory()->employed()->create();

    $wrestlers = collect([$wrestler1, $wrestler2]);
    $sideNumber = 1;

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, $wrestlers, $sideNumber));

    // Should create competitor records for both wrestlers
    assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler1->id,
        'competitor_type' => $wrestler1->getMorphClass(),
        'match_side_id' => $match->sides()->where('position', $sideNumber)->firstOrFail()->id,
    ]);

    assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler2->id,
        'competitor_type' => $wrestler2->getMorphClass(),
        'match_side_id' => $match->sides()->where('position', $sideNumber)->firstOrFail()->id,
    ]);

    // Match should have both wrestlers
    expect($match->refresh()->competitors()->count())->toBe(2);
});

test('it adds wrestlers to different sides', function () {
    $match = EventMatch::factory()->create();
    $wrestler1 = Wrestler::factory()->employed()->create();
    $wrestler2 = Wrestler::factory()->employed()->create();

    // Add wrestler1 to side 1
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler1]), 1));

    // Add wrestler2 to side 2
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler2]), 2));

    // Should create competitor records with different side numbers
    assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler1->id,
        'competitor_type' => $wrestler1->getMorphClass(),
        'match_side_id' => $match->sides()->where('position', 1)->firstOrFail()->id,
    ]);

    assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler2->id,
        'competitor_type' => $wrestler2->getMorphClass(),
        'match_side_id' => $match->sides()->where('position', 2)->firstOrFail()->id,
    ]);

    expect($match->refresh()->competitors()->count())->toBe(2);
});

test('it rejects the entire assignment when any wrestler is ineligible', function () {
    $match = EventMatch::factory()->create();
    $eligibleWrestler = Wrestler::factory()->employed()->create();
    $ineligibleWrestler = Wrestler::factory()->retired()->create(); // Not bookable

    $wrestlers = collect([$eligibleWrestler, $ineligibleWrestler]);
    $sideNumber = 1;

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, $wrestlers, $sideNumber)))
        ->toThrow(EntityNotAvailableException::class, 'Selected wrestlers must all be eligible for match assignment.')
        ->and($match->competitors()->count())->toBe(0)
        ->and($match->sides()->count())->toBe(0);
});

test('it throws exception when no eligible wrestlers provided', function () {
    $match = EventMatch::factory()->create();
    $ineligibleWrestler = Wrestler::factory()->retired()->create();

    $wrestlers = collect([$ineligibleWrestler]);
    $sideNumber = 1;

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, $wrestlers, $sideNumber)))
        ->toThrow(EntityNotAvailableException::class, 'Selected wrestlers must all be eligible for match assignment.');
});

test('it reloads wrestlers before checking assignment eligibility', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $staleWrestler = Wrestler::query()->findOrFail($wrestler->id);

    $wrestler->delete();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$staleWrestler]), 1)))
        ->toThrow(EntityNotAvailableException::class)
        ->and($match->competitors()->exists())->toBeFalse();
});

test('it rejects a wrestler booked on another event at the same time', function () {
    $eventDate = now()->addWeek();
    $existingEvent = Event::factory()->create(['date' => $eventDate]);
    $targetEvent = Event::factory()->create(['date' => $eventDate]);
    $existingMatch = EventMatch::factory()->forEvent($existingEvent)->create();
    $targetMatch = EventMatch::factory()->forEvent($targetEvent)->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $existingSide = wrestlerMatchSide($existingMatch, 1);

    $existingMatch->competitors()->create([
        'match_side_id' => $existingSide->id,
        'competitor_id' => $wrestler->id,
        'competitor_type' => $wrestler->getMorphClass(),
    ]);

    expect(fn () => assignWithinLockedMatch($targetMatch, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler]), 1)))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$wrestler->name}] is already booked at this event time.")
        ->and($targetMatch->competitors()->count())->toBe(0);
});

test('it allows a wrestler booked at a different event time', function () {
    $existingEvent = Event::factory()->create(['date' => now()->addWeek()]);
    $targetEvent = Event::factory()->create(['date' => now()->addWeek()->addHour()]);
    $existingMatch = EventMatch::factory()->forEvent($existingEvent)->create();
    $targetMatch = EventMatch::factory()->forEvent($targetEvent)->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $existingSide = wrestlerMatchSide($existingMatch, 1);

    $existingMatch->competitors()->create([
        'match_side_id' => $existingSide->id,
        'competitor_id' => $wrestler->id,
        'competitor_type' => $wrestler->getMorphClass(),
    ]);

    assignWithinLockedMatch($targetMatch, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler]), 1));

    expect($targetMatch->competitors()->count())->toBe(1);
});

test('it assigns a repeated wrestler only once', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler, $wrestler]), 1));

    expect($match->competitors()->count())->toBe(1);
});

test('it books a wrestler on a past event dated while they were employed', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->subDays(10)]))->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->employments()->update(['started_at' => now()->subDays(30)]);

    // Act
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler]), 1));

    // Assert
    expect($match->refresh()->competitors()->count())->toBe(1);
});

test('it refuses a wrestler on an event dated before their employment started', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->subDays(10)]))->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Act
    $book = fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler]), 1));

    // Assert
    expect($book)->toThrow(EntityNotAvailableException::class)
        ->and($match->refresh()->competitors()->count())->toBe(0);
});

test('it books a wrestler hired for a later event', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->addDays(10)]))->create();
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();

    // Act
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler]), 1));

    // Assert
    expect($match->refresh()->competitors()->count())->toBe(1);
});

test('it refuses a wrestler on an event dated before their future employment starts', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->addDays(5)]))->create();
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();
    $wrestler->employments()->update(['started_at' => now()->addDays(10)]);

    // Act
    $book = fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddWrestlersToMatchAction::class)->handle($lockedMatch, collect([$wrestler]), 1));

    // Assert
    expect($book)->toThrow(EntityNotAvailableException::class);
});
