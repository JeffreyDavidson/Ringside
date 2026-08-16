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

    expect(fn () => resolve(AddRefereesToMatchAction::class)->handle($match, $referees))
        ->toThrow(EntityNotAvailableException::class, 'Selected referees must all be eligible for match assignment.');
});

test('it rejects the entire assignment when any referee is unavailable', function () {
    $match = EventMatch::factory()->create();
    $availableReferee = Referee::factory()->bookable()->create();
    $unavailableReferee = Referee::factory()->retired()->create();

    expect(fn () => resolve(AddRefereesToMatchAction::class)->handle(
        $match,
        collect([$availableReferee, $unavailableReferee]),
    ))->toThrow(EntityNotAvailableException::class);

    expect($match->referees()->count())->toBe(0);
});

test('it allows a referee to officiate multiple matches on one event card', function () {
    $event = Event::factory()->scheduled()->create();
    $existingMatch = EventMatch::factory()->forEvent($event)->create();
    $targetMatch = EventMatch::factory()->forEvent($event)->create();
    $referee = Referee::factory()->bookable()->create();

    $existingMatch->referees()->attach($referee);

    resolve(AddRefereesToMatchAction::class)->handle($targetMatch, $referee->newCollection([$referee]));

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

    expect(fn () => resolve(AddRefereesToMatchAction::class)->handle($targetMatch, $referee->newCollection([$referee])))
        ->toThrow(SchedulingConflictException::class, "Referee [{$referee->full_name}] is already assigned to another event at this time.");

    expect($targetMatch->referees()->count())->toBe(0);
});

test('it assigns a repeated referee only once', function () {
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->bookable()->create();

    resolve(AddRefereesToMatchAction::class)->handle($match, $referee->newCollection([$referee, $referee]));

    expect($match->referees()->count())->toBe(1);
});
