<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CancelAction;
use App\Actions\Roster\Availability\CreateAction;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

/**
 * The bookable() builder scopes feed the match form search. RosterBookingEligibility stays the
 * server-side authority, so every roster state must produce the same answer from both.
 */
test('the bookable scope agrees with roster booking eligibility for individuals', function (string $modelClass, string $state, bool $expected): void {
    // Arrange
    $rosterMember = $modelClass::factory()->{$state === 'employedAndRetired' ? 'employed' : $state}()->create();

    if ($state === 'employedAndRetired') {
        Retirement::factory()->started(now())->for($rosterMember, 'retirable')->create();
    }

    // Act
    $scopeAllows = $modelClass::query()->bookable()->whereKey($rosterMember)->exists();
    $eligibilityAllows = resolve(RosterBookingEligibility::class)->allows($rosterMember);

    // Assert
    expect($scopeAllows)->toBe($eligibilityAllows)
        ->and($scopeAllows)->toBe($expected);
})->with([
    'bookable wrestler' => [Wrestler::class, 'bookable', true],
    'unemployed wrestler' => [Wrestler::class, 'unemployed', false],
    'future employment wrestler' => [Wrestler::class, 'withFutureEmployment', false],
    'released wrestler' => [Wrestler::class, 'released', false],
    'retired wrestler' => [Wrestler::class, 'retired', false],
    'employed and retired wrestler' => [Wrestler::class, 'employedAndRetired', false],
    'injured wrestler' => [Wrestler::class, 'injured', false],
    'suspended wrestler' => [Wrestler::class, 'suspended', false],
    'bookable referee' => [Referee::class, 'bookable', true],
    'unemployed referee' => [Referee::class, 'unemployed', false],
    'future employment referee' => [Referee::class, 'withFutureEmployment', false],
    'released referee' => [Referee::class, 'released', false],
    'retired referee' => [Referee::class, 'retired', false],
    'employed and retired referee' => [Referee::class, 'employedAndRetired', false],
    'injured referee' => [Referee::class, 'injured', false],
    'suspended referee' => [Referee::class, 'suspended', false],
]);

test('date-aware tag-team candidates agree with the authority for current and former members', function (bool $current, bool $cancelled): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $team = TagTeam::factory()->bookable()->for($promotion)->create();
    $member = $team->currentWrestlers()->firstOrFail();
    $member->forceFill(['promotion_id' => $promotion->id])->save();
    $actor = administrator();
    $entry = resolve(CreateAction::class)->handle($member, '2026-10-17', '2026-10-17', null, $actor);
    if ($cancelled) {
        resolve(CancelAction::class)->handle($member, $entry->id, $actor);
    }
    if (! $current) {
        $team->wrestlers()->updateExistingPivot($member, ['left_at' => now()]);
        $replacement = Wrestler::factory()->bookable()->for($promotion)->create();
        $team->wrestlers()->attach($replacement, ['joined_at' => now()]);
    }
    $team->refresh();

    // Act
    $sql = TagTeam::query()->bookableOn('2026-10-17')->whereKey($team)->exists();
    $authority = resolve(RosterBookingEligibility::class)->allows($team) && resolve(RosterDateAvailability::class)->allowsOn($team, '2026-10-17');

    // Assert
    expect($sql)->toBe($authority)->and($sql)->toBe(! $current || $cancelled);
})->with([[true, false], [true, true], [false, false]]);

test('the bookable scope agrees with roster booking eligibility for tag teams', function (string $state, bool $expected): void {
    // Arrange
    $tagTeam = match ($state) {
        'unemployed',
        'withFutureEmployment',
        'released',
        'retired',
        'suspended' => TagTeam::factory()->{$state}()->create(),
        default => TagTeam::factory()->bookable()->create(),
    };
    $member = $tagTeam->currentWrestlers()->firstOrFail();

    match ($state) {
        'memberInjured' => Injury::factory()->started(now())->for($member, 'injurable')->create(),
        'memberSuspended' => Suspension::factory()->started(now())->for($member, 'suspendable')->create(),
        'memberRetired' => Retirement::factory()->started(now())->for($member, 'retirable')->create(),
        'memberUnemployed' => $member->employments()->delete(),
        'memberTrashed' => $member->delete(),
        'memberFormerlyInjured' => Injury::factory()->started(now()->subDays(3))->ended(now()->subDay())->for($member, 'injurable')->create(),
        'teamEmployedAndRetired' => Retirement::factory()->started(now())->for($tagTeam, 'retirable')->create(),
        'fewerThanTwoMembers' => $tagTeam->currentWrestlers()->updateExistingPivot($member, ['left_at' => now()]),
        'noMembers' => $tagTeam->currentWrestlers()->updateExistingPivot(
            $tagTeam->currentWrestlers()->pluck('wrestlers.id')->all(),
            ['left_at' => now()],
        ),
        default => null,
    };

    $tagTeam->refresh();

    // Act
    $scopeAllows = TagTeam::query()->bookable()->whereKey($tagTeam)->exists();
    $eligibilityAllows = resolve(RosterBookingEligibility::class)->allows($tagTeam);

    // Assert
    expect($scopeAllows)->toBe($eligibilityAllows)
        ->and($scopeAllows)->toBe($expected);
})->with([
    'bookable' => ['bookable', true],
    'unemployed' => ['unemployed', false],
    'future employment' => ['withFutureEmployment', false],
    'released' => ['released', false],
    'retired' => ['retired', false],
    'suspended team' => ['suspended', false],
    'employed and retired team' => ['teamEmployedAndRetired', false],
    'member injured' => ['memberInjured', false],
    'member suspended' => ['memberSuspended', false],
    'member retired' => ['memberRetired', false],
    'member unemployed' => ['memberUnemployed', false],
    'member soft deleted' => ['memberTrashed', false],
    'member with a past injury' => ['memberFormerlyInjured', true],
    'fewer than two members' => ['fewerThanTwoMembers', false],
    'no members' => ['noMembers', false],
]);
