<?php

test('invoke suspends a bookable referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::SUSPENDED);
});

test('invoke throws exception for suspending a non suspendable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $referee));
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
