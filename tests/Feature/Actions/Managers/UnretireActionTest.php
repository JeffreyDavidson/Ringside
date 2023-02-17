<?php

use App\Actions\Managers\UnretireAction;
use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Manager;

test('invoke calls unretire action and redirects', function () {
    $manager = Manager::factory()->retired()->create();

    UnretireAction::run($manager);

    expect($manager->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke throws exception for unretiring a non unretirable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    UnretireAction::run($manager);
})->throws(CannotBeUnretiredException::class)->with([
    'available',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
