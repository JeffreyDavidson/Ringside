<?php

declare(strict_types=1);

use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it adds a single wrestler to a match', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    $wrestlers = collect([$wrestler]);
    $sideNumber = 1;

    resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, $sideNumber);

    // Should create competitor record
    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler->id,
        'competitor_type' => Wrestler::class,
        'side_number' => $sideNumber,
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

    resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, $sideNumber);

    // Should create competitor records for both wrestlers
    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler1->id,
        'competitor_type' => Wrestler::class,
        'side_number' => $sideNumber,
    ]);

    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler2->id,
        'competitor_type' => Wrestler::class,
        'side_number' => $sideNumber,
    ]);

    // Match should have both wrestlers
    expect($match->refresh()->competitors()->count())->toBe(2);
});

test('it adds wrestlers to different sides', function () {
    $match = EventMatch::factory()->create();
    $wrestler1 = Wrestler::factory()->employed()->create();
    $wrestler2 = Wrestler::factory()->employed()->create();

    // Add wrestler1 to side 1
    resolve(AddWrestlersToMatchAction::class)->handle($match, collect([$wrestler1]), 1);

    // Add wrestler2 to side 2
    resolve(AddWrestlersToMatchAction::class)->handle($match, collect([$wrestler2]), 2);

    // Should create competitor records with different side numbers
    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler1->id,
        'competitor_type' => Wrestler::class,
        'side_number' => 1,
    ]);

    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $wrestler2->id,
        'competitor_type' => Wrestler::class,
        'side_number' => 2,
    ]);

    expect($match->refresh()->competitors()->count())->toBe(2);
});

test('it filters out ineligible wrestlers', function () {
    $match = EventMatch::factory()->create();
    $eligibleWrestler = Wrestler::factory()->employed()->create();
    $ineligibleWrestler = Wrestler::factory()->retired()->create(); // Not bookable

    $wrestlers = collect([$eligibleWrestler, $ineligibleWrestler]);
    $sideNumber = 1;

    resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, $sideNumber);

    // Should only add the eligible wrestler
    $this->assertDatabaseHas('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $eligibleWrestler->id,
        'competitor_type' => Wrestler::class,
        'side_number' => $sideNumber,
    ]);

    $this->assertDatabaseMissing('events_matches_competitors', [
        'match_id' => $match->id,
        'competitor_id' => $ineligibleWrestler->id,
        'competitor_type' => Wrestler::class,
    ]);

    expect($match->refresh()->competitors()->count())->toBe(1);
});

test('it throws exception when no eligible wrestlers provided', function () {
    $match = EventMatch::factory()->create();
    $ineligibleWrestler = Wrestler::factory()->retired()->create();

    $wrestlers = collect([$ineligibleWrestler]);
    $sideNumber = 1;

    expect(fn () => resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, $sideNumber))
        ->toThrow(EntityNotAvailableException::class, 'No eligible wrestlers were provided for match assignment.');
});

test('it throws exception when side number is invalid', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    $wrestlers = collect([$wrestler]);
    $invalidSideNumber = 0;

    expect(fn () => resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, $invalidSideNumber))
        ->toThrow(InvalidMatchConfigurationException::class, 'Match side number [0] must be positive.');
});

test('it handles transaction rollback on failure', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->employed()->create();

    // Mock a database failure scenario by using invalid side number
    $wrestlers = collect([$wrestler]);
    $invalidSideNumber = -1;

    expect(fn () => resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, $invalidSideNumber))
        ->toThrow(InvalidMatchConfigurationException::class);

    // No competitors should be added due to transaction rollback
    expect($match->refresh()->competitors()->count())->toBe(0);
});

test('it rejects a wrestler booked on another event at the same time', function () {
    $eventDate = now()->addWeek();
    $existingEvent = Event::factory()->create(['date' => $eventDate]);
    $targetEvent = Event::factory()->create(['date' => $eventDate]);
    $existingMatch = EventMatch::factory()->forEvent($existingEvent)->create();
    $targetMatch = EventMatch::factory()->forEvent($targetEvent)->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    $existingMatch->competitors()->create([
        'competitor_id' => $wrestler->id,
        'competitor_type' => Wrestler::class,
        'side_number' => 1,
    ]);

    expect(fn () => resolve(AddWrestlersToMatchAction::class)->handle($targetMatch, collect([$wrestler]), 1))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$wrestler->name}] is already booked at this event time.");

    expect($targetMatch->competitors()->count())->toBe(0);
});

test('it allows a wrestler booked at a different event time', function () {
    $existingEvent = Event::factory()->create(['date' => now()->addWeek()]);
    $targetEvent = Event::factory()->create(['date' => now()->addWeek()->addHour()]);
    $existingMatch = EventMatch::factory()->forEvent($existingEvent)->create();
    $targetMatch = EventMatch::factory()->forEvent($targetEvent)->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    $existingMatch->competitors()->create([
        'competitor_id' => $wrestler->id,
        'competitor_type' => Wrestler::class,
        'side_number' => 1,
    ]);

    resolve(AddWrestlersToMatchAction::class)->handle($targetMatch, collect([$wrestler]), 1);

    expect($targetMatch->competitors()->count())->toBe(1);
});

test('it assigns a repeated wrestler only once', function () {
    $match = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    resolve(AddWrestlersToMatchAction::class)->handle($match, collect([$wrestler, $wrestler]), 1);

    expect($match->competitors()->count())->toBe(1);
});
