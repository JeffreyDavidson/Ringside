<?php

test('invoke unretires a retired wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($this->wrestler->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(WrestlerStatus::BOOKABLE);
});

test('invoke throws exception for unretiring a non unretirable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $wrestler));
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
