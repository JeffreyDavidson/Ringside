<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

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
