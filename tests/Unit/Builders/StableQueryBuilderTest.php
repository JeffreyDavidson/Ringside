<?php

use App\Models\Stable;

test('active stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $activeStables = Stable::active()->get();

    expect($activeStables)
        ->toHaveCount(1)
        ->collectionHas($activeStable);
});

test('future activated stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $futureActivatedStables = Stable::withFutureActivation()->get();

    expect($futureActivatedStables)
        ->toHaveCount(1)
        ->collectionHas($futureActivatedStable);
});

test('inactive stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $inactiveStables = Stable::inactive()->get();

    expect($inactiveStables)
        ->toHaveCount(1)
        ->collectionHas($inactiveStable);
});

test('retired stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $retiredStables = Stable::retired()->get();

    expect($retiredStables)
        ->toHaveCount(1)
        ->collectionHas($retiredStable);
});

test('unactivated stables can be retrieved', function () {
    $activeStable = Stable::factory()->active()->create();
    $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
    $inactiveStable = Stable::factory()->inactive()->create();
    $retiredStable = Stable::factory()->retired()->create();
    $unactivatedStable = Stable::factory()->unactivated()->create();

    $unactivatedStables = Stable::unactivated()->get();

    expect($unactivatedStables)
        ->toHaveCount(1)
        ->collectionHas($unactivatedStable);
});
