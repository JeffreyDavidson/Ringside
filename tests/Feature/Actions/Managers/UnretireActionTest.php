<?php

test('invoke calls unretire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($manager->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(ManagerStatus::AVAILABLE);
});

test('invoke throws exception for unretiring a non unretirable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $manager));
})->throws(CannotBeUnretiredException::class)->with([
    'available',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
