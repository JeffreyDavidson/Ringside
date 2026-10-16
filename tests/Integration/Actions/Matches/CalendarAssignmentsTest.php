<?php

declare(strict_types=1);

use App\Actions\Events\RestoreAction;
use App\Actions\Events\UpdateAction;
use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Data\Events\EventData;
use App\Data\Matches\EventMatchData;
use App\Enums\Matches\MatchType;
use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Lifecycle\Events\EventRosterSchedulingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Queries\Events\EventRosterAvailabilityQuery;

test('a standalone assignment rejects a date-unavailable wrestler and writes nothing', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 22:00:00']);
    $match = EventMatch::factory()->forEvent($event)->create();
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    resolve(CreateAction::class)->handle($person, '2026-10-17', '2026-10-17', null, administrator());

    // Act
    $assign = fn () => resolve(AddWrestlersToMatchAction::class)->handle($match, collect([$person]), 1);

    // Assert
    expect($assign)->toThrow(EntityNotAvailableException::class, $person->name)
        ->and($match->competitors()->count())->toBe(0)
        ->and($match->sides()->count())->toBe(0);
});

test('a match repair retains a previously booked unavailable person while rejecting a new unavailable replacement', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => now()->addWeek()]);
    $first = Wrestler::factory()->bookable()->for($promotion)->create();
    $second = Wrestler::factory()->bookable()->for($promotion)->create();
    $replacement = Wrestler::factory()->bookable()->for($promotion)->create();
    $referees = Referee::factory()->bookable()->for($promotion)->count(1)->create();
    $titles = Title::query()->whereKey([])->get();
    $original = new EventMatchData(MatchType::Singles, $referees, $titles, collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$second]]]), 'Original');
    $match = resolve(AddMatchForEventAction::class)->handle($event, $original);
    $day = ($event->date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    $actor = administrator();
    resolve(CreateAction::class)->handle($first, $day, $day, null, $actor);
    $data = new EventMatchData(MatchType::Singles, $referees, $titles, collect([1 => ['wrestlers' => [$first]], 2 => ['wrestlers' => [$replacement]]]), 'Repaired');

    // Act
    resolve(UpdateMatchAction::class)->handle($match, $data);
    resolve(CreateAction::class)->handle($second, $day, $day, null, $actor);
    $forged = fn () => resolve(UpdateMatchAction::class)->handle($match, $original);

    // Assert
    expect($match->refresh()->preview)->toBe('Repaired')
        ->and($match->competitors()->pluck('competitor_id')->all())->toEqualCanonicalizing([$first->id, $replacement->id])
        ->and($forged)->toThrow(EntityNotAvailableException::class)
        ->and($match->refresh()->preview)->toBe('Repaired');
});

test('scheduling an undated booked event rejects an unavailable local day and rolls back', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->for($promotion)->create(['date' => null, 'venue_id' => null]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
    $date = now()->addWeek();
    $day = $date->copy()->setTimezone('America/New_York')->toDateString();
    resolve(CreateAction::class)->handle($person, $day, $day, null, administrator());
    $data = new EventData($event->name, $date, null, 'New preview');

    // Act
    $schedule = fn () => resolve(UpdateAction::class)->handle($event, $data);

    // Assert
    expect($schedule)->toThrow(EntityNotAvailableException::class, $person->name)
        ->and($event->refresh()->date)->toBeNull()
        ->and($event->preview)->not->toBe('New preview');
});

test('restoring a future event checks its retained roster while historical restoration remains possible', function (bool $future): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $date = $future ? now()->addWeek() : now()->subWeek();
    $event = Event::factory()->for($promotion)->create(['date' => $date, 'venue_id' => null]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
    $day = $date->copy()->setTimezone('America/New_York')->toDateString();
    $actor = administrator();
    $entry = resolve(CreateAction::class)->handle($person, $day, $day, null, $actor);
    $event->delete();

    // Act
    $restore = fn () => resolve(RestoreAction::class)->handle($event);

    // Assert
    if ($future) {
        expect($restore)->toThrow(EntityNotAvailableException::class)
            ->and($event->refresh()->trashed())->toBeTrue();

        resolve(CancelAction::class)->handle($person, $entry->id, $actor);
    }
    $restore();
    expect($event->refresh()->trashed())->toBeFalse();
})->with([true, false]);

test('an undated card has no calendar conflict until a date is chosen', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['date' => null, 'venue_id' => null]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    $match = EventMatch::factory()->forEvent($event)->withCompetitors([$person])->create();
    resolve(CreateAction::class)->handle($person, '2026-10-17', '2026-10-17', null, administrator());
    $query = resolve(EventRosterAvailabilityQuery::class);
    $eligibility = resolve(EventRosterSchedulingEligibility::class);

    // Act
    $allowed = resolve(RosterDateAvailability::class)->allowsForEvent($person, $event);
    $issues = $query->get($event);
    $eligibility->ensureAvailableAt($event, null, lock: true);

    // Assert
    expect($allowed)->toBeTrue()->and($issues)->toBeEmpty()
        ->and($match->competitors()->count())->toBe(1);
});
