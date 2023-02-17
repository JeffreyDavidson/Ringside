<?php

use App\Actions\Managers\SuspendAction;
use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Manager;

test('invoke suspends an available manager and redirects', function () {
    $manager = Manager::factory()->available()->create();

    SuspendAction::run($manager);

    expect($manager->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toMatchObject(ManagerStatus::SUSPENDED);
});

test('invoke throws exception for suspending a non suspendable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    SuspendAction::run($manager);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
