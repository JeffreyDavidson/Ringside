<?php

declare(strict_types=1);

use App\Actions\Matches\AddRefereesToMatchAction;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;

test('it rejects match assignment when no referee is available', function () {
    $match = EventMatch::factory()->create();
    $referees = Referee::factory()->retired()->count(2)->create();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, $referees)))
        ->toThrow(EntityNotAvailableException::class, 'Selected referees must all be eligible for match assignment.');
});

test('it rejects the entire assignment when any referee is unavailable', function () {
    $match = EventMatch::factory()->create();
    $availableReferee = Referee::factory()->bookable()->create();
    $unavailableReferee = Referee::factory()->retired()->create();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle(
        $lockedMatch,
        collect([$availableReferee, $unavailableReferee]),
    )))->toThrow(EntityNotAvailableException::class)
        ->and($match->referees()->count())->toBe(0);
});

test('it reloads referees before checking assignment eligibility', function () {
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->bookable()->create();
    $staleReferee = Referee::query()->findOrFail($referee->id);

    $referee->delete();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, collect([$staleReferee]))))
        ->toThrow(EntityNotAvailableException::class)
        ->and($match->referees()->exists())->toBeFalse();
});

test('it allows a referee to officiate multiple matches on one event card', function () {
    $event = Event::factory()->scheduled()->create();
    $existingMatch = EventMatch::factory()->forEvent($event)->create();
    $targetMatch = EventMatch::factory()->forEvent($event)->create();
    $referee = Referee::factory()->bookable()->create();

    $existingMatch->referees()->attach($referee);

    assignWithinLockedMatch($targetMatch, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, $referee->newCollection([$referee])));

    expect($targetMatch->referees()->whereKey($referee->id)->exists())->toBeTrue();
});

test('it rejects a referee assigned to another event at the same time', function () {
    $eventDate = now()->addWeek();
    $existingEvent = Event::factory()->create(['date' => $eventDate]);
    $targetEvent = Event::factory()->create(['date' => $eventDate]);
    $existingMatch = EventMatch::factory()->forEvent($existingEvent)->create();
    $targetMatch = EventMatch::factory()->forEvent($targetEvent)->create();
    $referee = Referee::factory()->bookable()->create();

    $existingMatch->referees()->attach($referee);
    $referee->refresh();

    expect(fn () => assignWithinLockedMatch($targetMatch, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, $referee->newCollection([$referee]))))
        ->toThrow(SchedulingConflictException::class, "Referee [{$referee->full_name}] is already assigned to another event at this time.")
        ->and($targetMatch->referees()->count())->toBe(0);
});

test('it assigns a repeated referee only once', function () {
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->bookable()->create();

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, $referee->newCollection([$referee, $referee])));

    expect($match->referees()->count())->toBe(1);
});

test('it does not duplicate an existing referee assignment', function () {
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->bookable()->create();

    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, collect([$referee])));
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, collect([$referee])));

    expect($match->referees()->whereKey($referee)->count())->toBe(1);
});

test('it rejects an empty referee list without assigning anyone', function () {
    $match = EventMatch::factory()->create();

    expect(fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, collect())))
        ->toThrow(EntityNotAvailableException::class, 'Selected referees must all be eligible for match assignment.')
        ->and($match->referees()->exists())->toBeFalse();
});

test('it books a referee on a past event dated while they were employed', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->subDays(10)]))->create();
    $referee = Referee::factory()->employed()->create();
    $referee->employments()->update(['started_at' => now()->subDays(30)]);

    // Act
    assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, collect([$referee])));

    // Assert
    expect($match->referees()->count())->toBe(1);
});

test('it refuses a referee on an event dated before they were employed', function () {
    // Arrange
    $match = EventMatch::factory()->forEvent(Event::factory()->create(['date' => now()->subDays(10)]))->create();
    $referee = Referee::factory()->employed()->create();
    $referee->employments()->update(['started_at' => now()->subDays(1)]);

    // Act
    $book = fn () => assignWithinLockedMatch($match, fn (EventMatch $lockedMatch) => resolve(AddRefereesToMatchAction::class)->handle($lockedMatch, collect([$referee])));

    // Assert
    expect($book)->toThrow(EntityNotAvailableException::class)
        ->and($match->referees()->count())->toBe(0);
});
