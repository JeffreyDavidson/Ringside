<?php

declare(strict_types=1);

use App\Enums\Stables\StableActivityTransition;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Lifecycle\StableActivityEligibility;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;

test('establishment predicate stays aligned with its guard', function (string $factoryState, bool $canEstablish) {
    $stableFactory = Stable::factory();
    $stable = $factoryState === 'default'
        ? $stableFactory->create()
        : $stableFactory->{$factoryState}()->create();
    $eligibility = resolve(StableActivityEligibility::class);

    expect($eligibility->allows($stable, StableActivityTransition::Establish))->toBe($canEstablish);

    if ($canEstablish) {
        expect(fn () => $eligibility->ensureAllowed($stable, StableActivityTransition::Establish))->not->toThrow(CannotBeEstablishedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureAllowed($stable, StableActivityTransition::Establish))->toThrow(CannotBeEstablishedException::class);
})->with([
    'unformed with enough members' => ['withEmployedDefaultMembers', true],
    'unformed without enough members' => ['default', false],
    'active' => ['active', false],
    'disbanded' => ['disbanded', false],
    'retired' => ['retired', false],
]);

test('disbandment predicate stays aligned with its guard', function (string $factoryState, bool $canDisband) {
    $stableFactory = Stable::factory();
    $stable = $factoryState === 'default'
        ? $stableFactory->create()
        : $stableFactory->{$factoryState}()->create();
    $eligibility = resolve(StableActivityEligibility::class);

    expect($eligibility->allows($stable, StableActivityTransition::Disband))->toBe($canDisband);

    if ($canDisband) {
        expect(fn () => $eligibility->ensureAllowed($stable, StableActivityTransition::Disband))->not->toThrow(CannotBeDisbandedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureAllowed($stable, StableActivityTransition::Disband))->toThrow(CannotBeDisbandedException::class);
})->with([
    'active' => ['active', true],
    'unformed' => ['default', false],
    'disbanded' => ['disbanded', false],
    'future activation' => ['withFutureActivation', false],
]);

test('reunion predicate stays aligned with its guard', function () {
    $eligibleStable = Stable::factory()->disbanded()->create();
    $stableWithoutFormerMembers = Stable::factory()->create();
    ActivityPeriod::factory()
        ->for($stableWithoutFormerMembers, 'activeable')
        ->state([
            'started_at' => now()->subDays(2),
            'ended_at' => now()->subDay(),
        ])
        ->create();
    $eligibility = resolve(StableActivityEligibility::class);

    expect($eligibility->allows($eligibleStable, StableActivityTransition::Reunite))->toBeTrue()
        ->and($eligibility->allows($stableWithoutFormerMembers, StableActivityTransition::Reunite))->toBeFalse()
        ->and(fn () => $eligibility->ensureAllowed($eligibleStable, StableActivityTransition::Reunite))
        ->not->toThrow(CannotBeReunitedException::class)
        ->and(fn () => $eligibility->ensureAllowed($stableWithoutFormerMembers, StableActivityTransition::Reunite))
        ->toThrow(CannotBeReunitedException::class);
});

test('activity transitions reject a deleted stable', function () {
    $unformedStable = Stable::factory()->create();
    $activeStable = Stable::factory()->active()->create();
    $disbandedStable = Stable::factory()->disbanded()->create();
    $eligibility = resolve(StableActivityEligibility::class);

    $unformedStable->delete();
    $activeStable->delete();
    $disbandedStable->delete();

    expect($eligibility->allows($unformedStable, StableActivityTransition::Establish))->toBeFalse()
        ->and($eligibility->allows($activeStable, StableActivityTransition::Disband))->toBeFalse()
        ->and($eligibility->allows($disbandedStable, StableActivityTransition::Reunite))->toBeFalse();
});
