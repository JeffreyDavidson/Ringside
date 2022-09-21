<?php

test('invoke reinstates a suspended referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke throws exception for reinstating a non reinstatable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $referee));
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
