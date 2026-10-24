<?php

declare(strict_types=1);

use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;

/**
 * Pins the exact rows bookable() and bookableOn() select, so the clauses they share cannot drift
 * apart. The tag team cases pin the wrestler-level unavailability clause bookableOn() adds.
 */
test('bookableOn selects the same individuals as bookable when no date is given', function (string $modelClass): void {
    // Arrange
    $bookable = $modelClass::factory()
        ->bookable()
        ->create();
    $modelClass::factory()
        ->unemployed()
        ->create();
    $modelClass::factory()
        ->retired()
        ->create();
    $modelClass::factory()
        ->suspended()
        ->create();
    $modelClass::factory()
        ->injured()
        ->create();
    $modelClass::factory()
        ->released()
        ->create();
    $modelClass::factory()
        ->bookable()
        ->create()
        ->delete();

    // Act
    $withScope = $modelClass::query()->bookable()->pluck('id')->all();
    $withDateScope = $modelClass::query()->bookableOn(null)->pluck('id')->all();

    // Assert
    expect($withScope)->toBe([$bookable->id])
        ->and($withDateScope)->toBe([$bookable->id]);
})->with([Wrestler::class, Referee::class]);

test('bookableOn excludes individuals by unavailability only when it covers the date in their promotion', function (string $modelClass, string $starts, string $ends, bool $cancelled, bool $samePromotion, bool $excluded): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $person = $modelClass::factory()
        ->bookable()
        ->for($promotion)
        ->create();
    $other = $modelClass::factory()
        ->bookable()
        ->for($promotion)
        ->create();

    Unavailability::factory()
        ->for($person, 'person')
        ->create([
            'starts_on' => $starts,
            'ends_on' => $ends,
            'cancelled_at' => $cancelled ? now() : null,
            'promotion_id' => $samePromotion ? $person->promotion_id : Promotion::factory()->create()->id,
        ]);

    // Act
    $ids = $modelClass::query()->bookableOn('2026-10-17')->pluck('id')->all();

    // Assert
    expect($ids)->toContain($other->id)
        ->and(in_array($person->id, $ids, true))->toBe(! $excluded);
})->with([Wrestler::class, Referee::class])->with([
    'starts on the date' => ['2026-10-17', '2026-10-20', false, true, true],
    'ends on the date' => ['2026-10-10', '2026-10-17', false, true, true],
    'spans the date' => ['2026-10-10', '2026-10-20', false, true, true],
    'ended the day before' => ['2026-10-10', '2026-10-16', false, true, false],
    'starts the day after' => ['2026-10-18', '2026-10-20', false, true, false],
    'cancelled entry' => ['2026-10-10', '2026-10-20', true, true, false],
    'entry from another promotion' => ['2026-10-10', '2026-10-20', false, false, false],
]);

test('bookableOn reads employment at the given moment and everything else as it stands now', function (string $modelClass, string $state, bool $expected): void {
    // Arrange
    $at = Carbon::parse('2026-10-17 20:00:00');
    $person = $modelClass::factory()
        ->has(Employment::factory()->started(now()->subYear()), 'employments')
        ->create();

    match ($state) {
        'employment starts exactly at the moment' => $person->employments()->update(['started_at' => $at]),
        'employment starts after the moment' => $person->employments()->update(['started_at' => $at->copy()->addSecond()]),
        'employment ends exactly at the moment' => $person->employments()->update(['started_at' => $at->copy()->subYear(), 'ended_at' => $at]),
        'employment ended before the moment' => $person->employments()->update(['started_at' => $at->copy()->subYear(), 'ended_at' => $at->copy()->subSecond()]),
        'injured now' => Injury::factory()->started(now()->subDay())->for($person, 'injurable')->create(),
        'trashed' => $person->delete(),
        default => null,
    };

    // Act
    $ids = $modelClass::query()->bookableOn('2026-10-17', $at)->pluck('id')->all();

    // Assert
    expect(in_array($person->id, $ids, true))->toBe($expected);
})->with([Wrestler::class, Referee::class])->with([
    'employed throughout' => ['plain', true],
    'employment starts exactly at the moment' => ['employment starts exactly at the moment', true],
    'employment starts after the moment' => ['employment starts after the moment', false],
    'employment ends exactly at the moment' => ['employment ends exactly at the moment', true],
    'employment ended before the moment' => ['employment ended before the moment', false],
    'injured now' => ['injured now', false],
    'trashed' => ['trashed', false],
]);

test('tag team bookableOn excludes teams whose current wrestler is unavailable on the date', function (string $starts, string $ends, bool $cancelled, bool $excluded): void {
    // Arrange
    $team = TagTeam::factory()
        ->bookable()
        ->create();
    $other = TagTeam::factory()
        ->bookable()
        ->create();
    $member = $team->currentWrestlers()->firstOrFail();
    $member->forceFill(['promotion_id' => Promotion::factory()->create()->id])->save();

    Unavailability::factory()
        ->for($member, 'person')
        ->create([
            'starts_on' => $starts,
            'ends_on' => $ends,
            'cancelled_at' => $cancelled ? now() : null,
            'promotion_id' => $member->promotion_id,
        ]);

    // Act
    $ids = TagTeam::query()->bookableOn('2026-10-17')->pluck('id')->all();
    $idsAtMoment = TagTeam::query()->bookableOn('2026-10-17', now())->pluck('id')->all();

    // Assert
    expect($ids)->toContain($other->id)
        ->and(in_array($team->id, $ids, true))->toBe(! $excluded)
        ->and($idsAtMoment)->toContain($other->id)
        ->and(in_array($team->id, $idsAtMoment, true))->toBe(! $excluded);
})->with([
    'starts on the date' => ['2026-10-17', '2026-10-20', false, true],
    'ends on the date' => ['2026-10-10', '2026-10-17', false, true],
    'ended the day before' => ['2026-10-10', '2026-10-16', false, false],
    'starts the day after' => ['2026-10-18', '2026-10-20', false, false],
    'cancelled entry' => ['2026-10-10', '2026-10-20', true, false],
]);

test('tag team bookable and bookableOn without a date select the same teams', function (): void {
    // Arrange
    $bookable = TagTeam::factory()
        ->bookable()
        ->create();
    TagTeam::factory()
        ->retired()
        ->create();
    TagTeam::factory()
        ->suspended()
        ->create();
    TagTeam::factory()
        ->unemployed()
        ->create();
    $memberInjured = TagTeam::factory()
        ->bookable()
        ->create();
    Injury::factory()
        ->started(now())
        ->for($memberInjured->currentWrestlers()->firstOrFail(), 'injurable')
        ->create();

    // Act
    $withScope = TagTeam::query()->bookable()->pluck('id')->all();
    $withDateScope = TagTeam::query()->bookableOn(null)->pluck('id')->all();

    // Assert
    expect($withScope)->toBe([$bookable->id])
        ->and($withDateScope)->toBe([$bookable->id]);
});
