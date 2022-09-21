<?php

test('invoke retires a bookable wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(WrestlerStatus::RETIRED);
});

test('invoke retires an injured wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->injured()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(WrestlerStatus::RETIRED);
});

test('invoke retires a suspended wrestler and redirects', function () {
    $wrestler = Wrestler::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($wrestler->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(WrestlerStatus::RETIRED);
});

test('retiring a bookable wrestler on a bookable tag team makes tag team unbookable', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = $tagTeam->wrestlers()->first();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    expect($tagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::UNBOOKABLE);
});

test('invoke throws exception for retiring a non retirable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $wrestler));
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'released',
    'unemployed',
]);
