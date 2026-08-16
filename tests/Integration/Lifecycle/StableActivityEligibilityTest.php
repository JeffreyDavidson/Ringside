<?php

declare(strict_types=1);

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

    expect($eligibility->canEstablish($stable))->toBe($canEstablish);

    if ($canEstablish) {
        expect(fn () => $eligibility->ensureCanEstablish($stable))->not->toThrow(CannotBeEstablishedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanEstablish($stable))->toThrow(CannotBeEstablishedException::class);
})->with([
    'unformed' => ['default', true],
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

    expect($eligibility->canDisband($stable))->toBe($canDisband);

    if ($canDisband) {
        expect(fn () => $eligibility->ensureCanDisband($stable))->not->toThrow(CannotBeDisbandedException::class);

        return;
    }

    expect(fn () => $eligibility->ensureCanDisband($stable))->toThrow(CannotBeDisbandedException::class);
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

    expect($eligibility->canReunite($eligibleStable))->toBeTrue()
        ->and($eligibility->canReunite($stableWithoutFormerMembers))->toBeFalse()
        ->and(fn () => $eligibility->ensureCanReunite($eligibleStable))
        ->not->toThrow(CannotBeReunitedException::class)
        ->and(fn () => $eligibility->ensureCanReunite($stableWithoutFormerMembers))
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

    expect($eligibility->canEstablish($unformedStable))->toBeFalse()
        ->and($eligibility->canDisband($activeStable))->toBeFalse()
        ->and($eligibility->canReunite($disbandedStable))->toBeFalse();
});
