<?php

declare(strict_types=1);

use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;

it('allows only a never-active stable to be established', function () {
    $unformedStable = Stable::factory()->create();
    $disbandedStable = Stable::factory()->disbanded()->create();

    expect($unformedStable->canBeEstablished())->toBeTrue()
        ->and($disbandedStable->canBeEstablished())->toBeFalse();

    expect(fn () => $unformedStable->ensureCanBeEstablished())
        ->not->toThrow(CannotBeEstablishedException::class);
    expect(fn () => $disbandedStable->ensureCanBeEstablished())
        ->toThrow(CannotBeEstablishedException::class);
});

it('keeps disbandment boolean and exception validation aligned', function () {
    $activeStable = Stable::factory()->active()->create();
    $unformedStable = Stable::factory()->create();
    $futureStable = Stable::factory()->withFutureActivation()->create();

    expect($activeStable->canBeDisbanded())->toBeTrue()
        ->and($unformedStable->canBeDisbanded())->toBeFalse()
        ->and($futureStable->canBeDisbanded())->toBeFalse();

    expect(fn () => $activeStable->ensureCanBeDisbanded())
        ->not->toThrow(CannotBeDisbandedException::class);
    expect(fn () => $unformedStable->ensureCanBeDisbanded())
        ->toThrow(CannotBeDisbandedException::class);
    expect(fn () => $futureStable->ensureCanBeDisbanded())
        ->toThrow(CannotBeDisbandedException::class);
});

it('uses reunion exceptions for invalid activity states', function () {
    $unformedStable = Stable::factory()->create();
    $activeStable = Stable::factory()->active()->create();
    $retiredStable = Stable::factory()->retired()->create();

    expect($unformedStable->canBeReunited())->toBeFalse()
        ->and($activeStable->canBeReunited())->toBeFalse()
        ->and($retiredStable->canBeReunited())->toBeFalse();

    expect(fn () => $unformedStable->ensureCanBeReunited())
        ->toThrow(CannotBeReunitedException::class);
    expect(fn () => $activeStable->ensureCanBeReunited())
        ->toThrow(CannotBeReunitedException::class);
    expect(fn () => $retiredStable->ensureCanBeReunited())
        ->toThrow(CannotBeReunitedException::class);
});

it('keeps reunion former-member validation aligned', function () {
    $eligibleStable = Stable::factory()->disbanded()->create();
    $stableWithoutFormerMembers = Stable::factory()->create();
    StableActivityPeriod::factory()
        ->for($stableWithoutFormerMembers)
        ->state([
            'started_at' => now()->subDays(2),
            'ended_at' => now()->subDay(),
        ])
        ->create();

    expect($eligibleStable->canBeReunited())->toBeTrue()
        ->and($stableWithoutFormerMembers->canBeReunited())->toBeFalse();

    expect(fn () => $eligibleStable->ensureCanBeReunited())
        ->not->toThrow(CannotBeReunitedException::class);
    expect(fn () => $stableWithoutFormerMembers->ensureCanBeReunited())
        ->toThrow(CannotBeReunitedException::class);
});

it('rejects activity transitions for a deleted stable', function () {
    $unformedStable = Stable::factory()->create();
    $activeStable = Stable::factory()->active()->create();
    $disbandedStable = Stable::factory()->disbanded()->create();

    $unformedStable->delete();
    $activeStable->delete();
    $disbandedStable->delete();

    expect($unformedStable->canBeEstablished())->toBeFalse()
        ->and($activeStable->canBeDisbanded())->toBeFalse()
        ->and($disbandedStable->canBeReunited())->toBeFalse()
        ->and(fn () => $unformedStable->ensureCanBeEstablished())
        ->toThrow(CannotBeEstablishedException::class)
        ->and(fn () => $activeStable->ensureCanBeDisbanded())
        ->toThrow(CannotBeDisbandedException::class)
        ->and(fn () => $disbandedStable->ensureCanBeReunited())
        ->toThrow(CannotBeReunitedException::class);
});
