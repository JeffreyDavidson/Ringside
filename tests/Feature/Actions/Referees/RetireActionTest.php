<?php

test('invoke retires a retirable referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(RefereeStatus::RETIRED);
});

test('invoke throws exception for retiring a non retirable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $referee));
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'released',
    'unemployed',
]);
