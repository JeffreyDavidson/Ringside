<?php

declare(strict_types=1);

use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Lifecycle\TagTeamSuspensionEligibility;
use App\Models\TagTeams\TagTeam;

test('suspension predicate stays aligned with its guard', function (string $factoryState, bool $canSuspend) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamSuspensionEligibility::class);

    expect($eligibility->canSuspend($tagTeam))->toBe($canSuspend);

    if ($canSuspend) {
        expect(fn () => $eligibility->ensureCanSuspend($tagTeam))->not->toThrow(CannotBeSuspendedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanSuspend($tagTeam))->toThrow(CannotBeSuspendedException::class);
})->with([
    'employed' => ['employed', true],
    'suspended' => ['suspended', false],
    'unemployed' => ['unemployed', false],
    'released' => ['released', false],
]);

test('reinstatement predicate stays aligned with its guard', function (string $factoryState, bool $canReinstate) {
    $tagTeam = TagTeam::factory()->{$factoryState}()->create();
    $eligibility = resolve(TagTeamSuspensionEligibility::class);

    expect($eligibility->canReinstate($tagTeam))->toBe($canReinstate);

    if ($canReinstate) {
        expect(fn () => $eligibility->ensureCanReinstate($tagTeam))->not->toThrow(CannotBeReinstatedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanReinstate($tagTeam))->toThrow(CannotBeReinstatedException::class);
})->with([
    'suspended' => ['suspended', true],
    'employed' => ['employed', false],
    'unemployed' => ['unemployed', false],
]);
