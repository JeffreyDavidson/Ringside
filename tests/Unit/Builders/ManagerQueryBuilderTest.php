<?php

use App\Models\Manager;

test('available managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $availableManagers = Manager::available()->get();

    expect($availableManagers)
        ->toHaveCount(1)
        ->collectionHas($availableManager);
});

test('future employed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $futureEmployedManagers = Manager::futureEmployed()->get();

    expect($futureEmployedManagers)
        ->toHaveCount(1)
        ->collectionHas($futureEmployedManager);
});

test('suspended managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $suspendedManagers = Manager::suspended()->get();

    expect($suspendedManagers)
        ->toHaveCount(1)
        ->collectionHas($suspendedManager);
});

test('released managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $releasedManagers = Manager::released()->get();

    expect($releasedManagers)
        ->toHaveCount(1)
        ->collectionHas($releasedManager);
});

test('retired managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $retiredManagers = Manager::retired()->get();

    expect($retiredManagers)
        ->toHaveCount(1)
        ->collectionHas($retiredManager);
});

test('unemployed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $unemployedManagers = Manager::unemployed()->get();

    expect($unemployedManagers)
        ->toHaveCount(1)
        ->collectionHas($unemployedManager);
});

test('injured managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->available()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $injuredManagers = Manager::injured()->get();

    expect($injuredManagers)
        ->toHaveCount(1)
        ->collectionHas($injuredManager);
});
