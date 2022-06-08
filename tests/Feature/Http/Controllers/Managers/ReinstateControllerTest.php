<?php

use App\Enums\ManagerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Models\Manager;

test('invoke reinstates a suspended manager and redirects', function () {
    $manager = Manager::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresH())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toBe(ManagerStatus::AVAILABLE);
});

test('a basic user cannot reinstate a suspended manager', function () {
    $manager = Manager::factory()->suspended()->create();

    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $manager))
        ->assertForbidden();
});

test('a guest cannot reinstate a suspended manager', function () {
    $manager = Manager::factory()->suspended()->create();

    $this->patch(action([ReinstateController::class], $manager))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for reinstating a non reinstatable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $manager));
})->throws(CannotBeReinstatedException::class)->with([
    'available',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
