<?php

test('invoke unretires a retired stable and its members and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($this->stable->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(StableStatus::ACTIVE);
});

test('invoke throws exception for unretiring a non unretirable stable', function ($factoryState) {
    $this->withoutExceptionHandling();

    $stable = Stable::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $stable));
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'withFutureActivation',
    'inactive',
    'unactivated',
]);
