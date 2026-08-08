<?php

declare(strict_types=1);

use App\Models\Managers\Manager;

test('employed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $availableManagers = Manager::available()->get();

    expect($availableManagers)
        ->toHaveCount(1)
        ->and($availableManagers->contains($availableManager))->toBeTrue();
});

test('future employed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $futureEmployedManagers = Manager::futureEmployed()->get();

    expect($futureEmployedManagers)
        ->toHaveCount(1)
        ->and($futureEmployedManagers->contains($futureEmployedManager))->toBeTrue();
});

test('suspended managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $suspendedManagers = Manager::suspended()->get();

    expect($suspendedManagers)
        ->toHaveCount(1)
        ->and($suspendedManagers->contains($suspendedManager))->toBeTrue();
});

test('released managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $releasedManagers = Manager::released()->get();

    expect($releasedManagers)
        ->toHaveCount(1)
        ->and($releasedManagers->contains($releasedManager))->toBeTrue();
});

test('retired managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $retiredManagers = Manager::retired()->get();

    expect($retiredManagers)
        ->toHaveCount(1)
        ->and($retiredManagers->contains($retiredManager))->toBeTrue();
});

test('unemployed managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $unemployedManagers = Manager::unemployed()->get();

    expect($unemployedManagers)
        ->toHaveCount(1)
        ->and($unemployedManagers->contains($unemployedManager))->toBeTrue();
});

test('injured managers can be retrieved', function () {
    $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
    $availableManager = Manager::factory()->employed()->create();
    $suspendedManager = Manager::factory()->suspended()->create();
    $retiredManager = Manager::factory()->retired()->create();
    $releasedManager = Manager::factory()->released()->create();
    $unemployedManager = Manager::factory()->unemployed()->create();
    $injuredManager = Manager::factory()->injured()->create();

    $injuredManagers = Manager::injured()->get();

    expect($injuredManagers)
        ->toHaveCount(1)
        ->and($injuredManagers->contains($injuredManager))->toBeTrue();
});
