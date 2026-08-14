<?php

declare(strict_types=1);

use App\Models\Stables\Stable;

test('established stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $activeStables = Stable::query()->established()->get();

    expect($activeStables)
        ->toHaveCount(1)
        ->and($activeStables->contains($activeStable))->toBeTrue();
});

test('future established stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $futureActivatedStables = Stable::query()->withFutureEstablishment()->get();

    expect($futureActivatedStables)
        ->toHaveCount(1)
        ->and($futureActivatedStables->contains($futureActivatedStable))->toBeTrue();
});

test('disbanded stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $inactiveStables = Stable::query()->disbanded()->get();

    expect($inactiveStables)
        ->toHaveCount(1)
        ->and($inactiveStables->contains($inactiveStable))->toBeTrue();
});

test('unestablished stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $unactivatedStables = Stable::query()->unestablished()->get();

    expect($unactivatedStables)
        ->toHaveCount(1)
        ->and($unactivatedStables->contains($unactivatedStable))->toBeTrue();
});
