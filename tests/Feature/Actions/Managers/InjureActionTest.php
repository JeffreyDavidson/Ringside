<?php

use App\Actions\Managers\InjureAction;
use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Manager;

test('invoke injures an available manager and redirects', function () {
    $manager = Manager::factory()->available()->create();

    InjureAction::run($manager);

    expect($manager->fresh())
        ->injuries->toHaveCount(1)
        ->status->toMatchObject(ManagerStatus::INJURED);
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    InjureAction::run($manager);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
