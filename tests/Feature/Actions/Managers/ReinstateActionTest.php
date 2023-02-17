<?php

use App\Actions\Managers\ReinstateAction;
use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\Manager;

test('invoke reinstates a suspended manager and redirects', function () {
    $manager = Manager::factory()->suspended()->create();

    ReinstateAction::run($manager);

    expect($manager->fresH())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke throws exception for reinstating a non reinstatable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    ReinstateAction::run($manager);
})->throws(CannotBeReinstatedException::class)->with([
    'available',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
