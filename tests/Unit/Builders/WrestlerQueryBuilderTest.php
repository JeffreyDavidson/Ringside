<?php

use App\Models\Wrestler;

test('bookable wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $bookableWrestlers = Wrestler::bookable()->get();

    expect($bookableWrestlers)
        ->toHaveCount(1)
        ->collectionHas($bookableWrestler);
});

test('future employed wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

    expect($futureEmployedWrestlers)
        ->toHaveCount(1)
        ->collectionHas($futureEmployedWrestler);
});

test('suspended wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $suspendedWrestlers = Wrestler::suspended()->get();

    expect($suspendedWrestlers)
        ->toHaveCount(1)
        ->collectionHas($suspendedWrestler);
});

test('released wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $releasedWrestlers = Wrestler::released()->get();

    expect($releasedWrestlers)
        ->toHaveCount(1)
        ->collectionHas($releasedWrestler);
});

test('retired wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $retiredWrestlers = Wrestler::retired()->get();

    expect($retiredWrestlers)
        ->toHaveCount(1)
        ->collectionHas($retiredWrestler);
});

test('unemployed wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $unemployedWrestlers = Wrestler::unemployed()->get();

    expect($unemployedWrestlers)
        ->toHaveCount(1)
        ->collectionHas($unemployedWrestler);
});

test('injured wrestlers can be retrieved', function () {
    $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
    $bookableWrestler = Wrestler::factory()->bookable()->create();
    $suspendedWrestler = Wrestler::factory()->suspended()->create();
    $retiredWrestler = Wrestler::factory()->retired()->create();
    $releasedWrestler = Wrestler::factory()->released()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    $injuredWrestler = Wrestler::factory()->injured()->create();

    $injuredWrestlers = Wrestler::injured()->get();

    expect($injuredWrestlers)
        ->toHaveCount(1)
        ->collectionHas($injuredWrestler);
});
