<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Rules\Shared\IsBookable;
use Illuminate\Support\Facades\Validator;

test('SQL and authoritative eligibility use inclusive promotion-local dates without changing lifecycle eligibility', function (string $type, string $instant, bool $expected): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $person = $type::factory()->bookable()->for($promotion)->create();
    $event = Event::factory()->for($promotion)->create(['date' => $instant]);
    resolve(CreateAction::class)->handle($person, new UnavailabilityData('2026-10-17', '2026-10-19', null), administrator());

    // Act
    $allowed = resolve(RosterDateAvailability::class)->allowsForEvent($person, $event);
    $sql = $type::query()->bookableOn($event->local_date?->toDateString())->whereKey($person)->exists();

    // Assert
    expect($allowed)->toBe($expected)
        ->and($sql)->toBe($expected)
        ->and(resolve(RosterBookingEligibility::class)->allows($person))->toBeTrue();
})->with([Wrestler::class, Referee::class])->with([
    ['2026-10-17 03:59:59', true],
    ['2026-10-17 04:00:00', false],
    ['2026-10-20 03:59:59', false],
    ['2026-10-20 04:00:00', true],
]);

test('calendar restrictions handle clock changes and distant promotion time zones', function (string $zone, string $day, string $instant): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => $zone]);
    $person = Wrestler::factory()->bookable()->for($promotion)->create();
    $event = Event::factory()->for($promotion)->create(['date' => $instant]);
    resolve(CreateAction::class)->handle($person, new UnavailabilityData($day, $day, null), administrator());

    // Act
    $allowed = resolve(RosterDateAvailability::class)->allowsForEvent($person, $event);
    $sql = Wrestler::query()->bookableOn($event->local_date?->toDateString())->whereKey($person)->exists();

    // Assert
    expect($allowed)->toBeFalse()->and($sql)->toBeFalse();
})->with([
    ['America/New_York', '2026-03-08', '2026-03-09 03:59:59'],
    ['America/New_York', '2026-11-01', '2026-11-01 05:30:00'],
    ['America/New_York', '2026-11-01', '2026-11-01 06:30:00'],
    ['Asia/Tokyo', '2026-10-17', '2026-10-16 15:00:00'],
    ['America/Los_Angeles', '2026-10-17', '2026-10-18 06:59:59'],
]);

test('promotion zone changes retain date strings and event instants but recompute their local-day restrictions', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $person = Wrestler::factory()->for($promotion)->create();
    $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 04:00:00']);
    $entry = resolve(CreateAction::class)->handle($person, new UnavailabilityData('2026-10-17', '2026-10-17', null), administrator());
    $dates = resolve(RosterDateAvailability::class);
    expect($dates->allowsForEvent($person, $event))->toBeFalse();

    // Act
    $promotion->update(['timezone' => 'America/Los_Angeles']);
    $event->refresh();
    $allowed = $dates->allowsForEvent($person, $event);

    // Assert
    expect($allowed)->toBeTrue()->and($entry->refresh()->starts_on)->toBe('2026-10-17')
        ->and($event->date?->toDateTimeString())->toBe('2026-10-17 04:00:00');
});

test('event-aware validation permits only stored date-unavailable assignments to remain on that match', function (string $model): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $person = $model::factory()->bookable()->for($promotion)->create();
    $person->employments()->update(['started_at' => '2026-01-01']);

    if ($person instanceof TagTeam) {
        $person->currentWrestlers->each(fn (Wrestler $member): int => $member->employments()->update(['started_at' => '2026-01-01']));
    }

    $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 18:00:00']);
    $match = EventMatch::factory()->forEvent($event)->create();
    $individual = $person instanceof TagTeam ? $person->currentWrestlers()->firstOrFail() : $person;
    $individual->forceFill(['promotion_id' => $promotion->id])->save();
    resolve(CreateAction::class)->handle($individual, new UnavailabilityData('2026-10-17', '2026-10-17', null), administrator());
    $fresh = Validator::make(['person' => $person->id], ['person' => [resolve(IsBookable::class, ['model' => $model, 'event' => $event])]]);

    if ($person instanceof Referee) {
        $match->referees()->attach($person);
    } else {
        $side = $match->sides()->create(['position' => 1]);
        $match->competitors()->create(['match_side_id' => $side->id, 'competitor_id' => $person->id, 'competitor_type' => $person->getMorphClass()]);
    }

    // Act
    $existing = Validator::make(['person' => $person->id], ['person' => [resolve(IsBookable::class, ['model' => $model, 'event' => $event, 'match' => $match])]]);
    $foreign = Event::factory()->for($promotion)->create(['date' => '2026-10-17 19:00:00']);
    $wrongMatch = Validator::make(['person' => $person->id], ['person' => [resolve(IsBookable::class, ['model' => $model, 'event' => $foreign, 'match' => $match])]]);

    // Assert
    expect($fresh->fails())->toBeTrue()->and($existing->passes())->toBeTrue()->and($wrongMatch->fails())->toBeTrue();
})->with([
    [Wrestler::class],
    [Referee::class],
    [TagTeam::class],
]);
