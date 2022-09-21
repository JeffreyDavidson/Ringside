<?php

test('invoke deactivates an active stable and its members and redirects', function () {
    $stable = Stable::factory()->active()->create();

    $this->actingAs(administrator())
        ->patch(action([DeactivateController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->activations->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(StableStatus::INACTIVE)
        ->currentWrestlers->each(fn ($wrestler) => $wrestler->status->toMatchObject(WrestlerStatus::RELEASED))
        ->currentTagTeams->each(fn ($tagTeam) => $tagTeam->status->toMatchObject(TagTeamStatus::RELEASED));
});

test('invoke throws exception for deactivating a non deactivatable stable', function ($factoryState) {
    $this->withoutExceptionHandling();

    $stable = Stable::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([DeactivateController::class], $stable));
})->throws(CannotBeDeactivatedException::class)->with([
    'inactive',
    'retired',
    'unactivated',
    'withFutureActivation',
]);
