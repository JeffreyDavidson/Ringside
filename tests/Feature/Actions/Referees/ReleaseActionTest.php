<?php

test('invoke releases a bookable referee and redirects', function () {
    $referee = Referee::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::RELEASED);
});

test('invoke releases an injured referee and redirects', function () {
    $referee = Referee::factory()->injured()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->injuries->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::RELEASED);
});

test('invoke releases an suspended referee and redirects', function () {
    $referee = Referee::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    expect($referee->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(RefereeStatus::RELEASED);
});

test('invoke throws an exception for releasing a non releasable referee', function ($factoryState) {
    $this->withoutExceptionHandling();

    $referee = Referee::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $referee));
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
