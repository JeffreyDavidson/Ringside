<?php

test('invoke reinstates a suspended manager and redirects', function () {
    $manager = Manager::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresH())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
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
