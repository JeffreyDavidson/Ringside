<?php

test('invoke injures an available manager and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    expect($this->manager->fresh())
        ->injuries->toHaveCount(1)
        ->status->toMatchObject(ManagerStatus::INJURED);
});

test('invoke throws exception for injuring a non injurable manager', function ($factoryState) {
    $this->withoutExceptionHandling();

    $manager = Manager::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $manager));
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
