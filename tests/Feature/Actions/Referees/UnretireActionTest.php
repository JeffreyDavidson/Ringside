<?php

test('invoke unretires a retired referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($this->referee->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::BOOKABLE);
});

test('invoke throws exception for unretiring a non unretirable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $referee));
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
