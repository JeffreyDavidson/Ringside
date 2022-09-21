<?php

test('invoke retires a retirable stable and its members and redirects', function ($factoryState) {
    $stable = Stable::factory()->$factoryState()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(StableStatus::RETIRED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler
                ->retirements->toHaveCount(1)
                ->status->toMatchObject(WrestlerStatus::RETIRED, $wrestler->status);
        })
        ->currentTagTeams->each(function ($tagTeam) {
            $tagTeam
                ->retirements->toHaveCount(1)
                ->status->toMatchObject(TagTeamStatus::RETIRED, $tagTeam->status);
        });
})->with([
    'active',
    'inactive',
]);

test('invoke throws exception for retiring a non retirable stable', function ($factoryState) {
    $this->withoutExceptionHandling();

    $stable = Stable::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $stable));
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureActivation',
    'unactivated',
]);
