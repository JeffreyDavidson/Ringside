<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Lifecycle\Employment;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('individual roster members are bookable only when employed and clear', function (string $modelClass, string $factoryState, bool $eligible): void {
    $rosterMember = $modelClass::factory()->{$factoryState}()->create();

    expect(resolve(RosterBookingEligibility::class)->allows($rosterMember))->toBe($eligible);
})->with([
    'employed wrestler' => [Wrestler::class, 'employed', true],
    'future employment wrestler' => [Wrestler::class, 'withFutureEmployment', false],
    'suspended wrestler' => [Wrestler::class, 'suspended', false],
    'injured wrestler' => [Wrestler::class, 'injured', false],
    'retired wrestler' => [Wrestler::class, 'retired', false],
    'released wrestler' => [Wrestler::class, 'released', false],
    'unemployed wrestler' => [Wrestler::class, 'unemployed', false],
    'employed referee' => [Referee::class, 'employed', true],
    'future employment referee' => [Referee::class, 'withFutureEmployment', false],
    'suspended referee' => [Referee::class, 'suspended', false],
    'injured referee' => [Referee::class, 'injured', false],
    'retired referee' => [Referee::class, 'retired', false],
    'released referee' => [Referee::class, 'released', false],
    'unemployed referee' => [Referee::class, 'unemployed', false],
]);

test('a tag team must satisfy its own roster state requirements', function (string $factoryState, bool $eligible) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    expect(resolve(RosterBookingEligibility::class)->allows($tagTeam))->toBe($eligible);
})->with([
    'available' => ['bookable', true],
    'future employed' => ['withFutureEmployment', false],
    'suspended' => ['suspended', false],
    'retired' => ['retired', false],
    'released' => ['released', false],
    'unemployed' => ['unemployed', false],
]);

test('a tag team requires at least two current wrestlers', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = $tagTeam->currentWrestlers()->firstOrFail();

    $tagTeam->currentWrestlers()->updateExistingPivot($wrestler, ['left_at' => now()]);
    $tagTeam->refresh();

    expect(resolve(RosterBookingEligibility::class)->allows($tagTeam))->toBeFalse();
});

test('a roster member with future employment is not bookable', function () {
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();

    expect($wrestler->status)->toBe(EmploymentStatus::FutureEmployment)
        ->and($wrestler->futureEmployment()->exists())->toBeTrue()
        ->and(resolve(RosterBookingEligibility::class)->allows($wrestler))->toBeFalse();
});

test('every current tag team wrestler must be eligible', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = $tagTeam->currentWrestlers()->firstOrFail();

    $wrestler->currentEmployment()->firstOrFail()->update(['ended_at' => now()]);
    $tagTeam->refresh();

    expect(resolve(RosterBookingEligibility::class)->allows($tagTeam))->toBeFalse();
});

test('an individual is bookable on the date they were employed', function (int $startedDaysAgo, ?int $endedDaysAgo, int $eventDaysAhead, bool $eligible): void {
    // Arrange
    $employment = Employment::factory()->started(now()->subDays($startedDaysAgo));

    if ($endedDaysAgo !== null) {
        $employment = $employment->ended(now()->subDays($endedDaysAgo));
    }

    $wrestler = Wrestler::factory()->has($employment, 'employments')->create();
    $referee = Referee::factory()->has($employment, 'employments')->create();
    $eventDate = now()->addDays($eventDaysAhead);

    // Act
    $wrestlerAllowed = resolve(RosterBookingEligibility::class)->allows($wrestler, $eventDate);
    $refereeAllowed = resolve(RosterBookingEligibility::class)->allows($referee, $eventDate);

    // Assert
    expect($wrestlerAllowed)->toBe($eligible)
        ->and($refereeAllowed)->toBe($eligible);
})->with([
    'employed and still employed, event after start' => [10, null, 5, true],
    'employed and still employed, back-filled event after start' => [10, null, -5, true],
    'employed now, event before the start date' => [10, null, -20, false],
    'future start, event after the start date' => [-3, null, 5, true],
    'future start, event before the start date' => [-3, null, 1, false],
    'released, event while employed' => [20, 10, -15, true],
    'released, event after release' => [20, 10, -5, false],
    'released, event in the future' => [20, 10, 5, false],
]);

test('an individual who is retired, suspended or injured now is not bookable on a date they were employed', function (string $state): void {
    // Arrange
    $wrestler = Wrestler::factory()->{$state}()->create();
    $wrestler->employments()->update(['started_at' => now()->subDays(30), 'ended_at' => null]);
    $eventDate = now()->subDays(10);

    // Act
    $allowed = resolve(RosterBookingEligibility::class)->allows($wrestler->refresh(), $eventDate);

    // Assert
    expect($allowed)->toBeFalse();
})->with(['suspended', 'injured', 'retired']);

test('a tag team is bookable only when it and every current wrestler were employed on the event date', function (int $teamStartedDaysAgo, int $memberStartedDaysAgo, int $eventDaysAhead, bool $eligible): void {
    // Arrange
    $tagTeam = TagTeam::factory()->bookable()->create();
    $tagTeam->employments()->update(['started_at' => now()->subDays($teamStartedDaysAgo)]);
    $tagTeam->currentWrestlers->each(fn (Wrestler $member): int => $member->employments()->update(['started_at' => now()->subDays(60)]));
    $tagTeam->currentWrestlers->firstOrFail()->employments()->update(['started_at' => now()->subDays($memberStartedDaysAgo)]);
    $tagTeam->refresh();

    // Act
    $allowed = resolve(RosterBookingEligibility::class)->allows($tagTeam, now()->addDays($eventDaysAhead));

    // Assert
    expect($allowed)->toBe($eligible);
})->with([
    'everyone employed before the event' => [10, 10, -5, true],
    'team not yet employed' => [10, 10, -20, false],
    'one member not yet employed' => [30, 10, -20, false],
    'team employed, member started later than the event' => [30, 5, -10, false],
]);

test('a tag team that is retired now is not bookable on a date it was employed', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->retired()->create();
    $tagTeam->employments()->update(['started_at' => now()->subDays(30), 'ended_at' => null]);

    // Act
    $allowed = resolve(RosterBookingEligibility::class)->allows($tagTeam->refresh(), now()->subDays(10));

    // Assert
    expect($allowed)->toBeFalse();
});
