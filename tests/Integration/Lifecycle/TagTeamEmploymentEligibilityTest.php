<?php

declare(strict_types=1);

use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Lifecycle\TagTeamEmploymentEligibility;
use App\Models\TagTeams\TagTeam;

test('employment predicate stays aligned with its guard', function (string $factoryState, bool $canEmploy) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamEmploymentEligibility::class);

    expect($eligibility->canEmploy($tagTeam))->toBe($canEmploy);

    if ($canEmploy) {
        expect(fn () => $eligibility->ensureCanEmploy($tagTeam))->not->toThrow(CannotBeEmployedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanEmploy($tagTeam))->toThrow(CannotBeEmployedException::class);
})->with([
    'unemployed with current wrestlers' => ['unemployed', true],
    'employed' => ['employed', false],
    'future employment' => ['withFutureEmployment', false],
    'retired' => ['retired', false],
    'released with current wrestlers' => ['released', true],
]);

test('release predicate stays aligned with its guard', function (string $factoryState, bool $canRelease) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamEmploymentEligibility::class);

    expect($eligibility->canRelease($tagTeam))->toBe($canRelease);

    if ($canRelease) {
        expect(fn () => $eligibility->ensureCanRelease($tagTeam))->not->toThrow(CannotBeReleasedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanRelease($tagTeam))->toThrow(CannotBeReleasedException::class);
})->with([
    'employed' => ['employed', true],
    'unemployed' => ['unemployed', false],
    'released' => ['released', false],
]);
