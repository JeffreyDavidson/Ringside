<?php

test('invoke activates an unactivated stable and employs its unemployed members and redirects', function () {
    $stable = Stable::factory()->unactivated()->withUnemployedDefaultMembers()->create();

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->activations->toHaveCount(1)
        ->status->toMatchObject(StableStatus::ACTIVE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(1)
                ->status->toMatchObject(WrestlerStatus::BOOKABLE);
        })
        ->currentTagTeams->each(function ($tagTeam) {
            $tagTeam->employments->toHaveCount(1)
                ->status->toMatchObject(TagTeamStatus::BOOKABLE);
        });
});

test('invoke activates a future activated stable with members and redirects', function () {
    $stable = Stable::factory()->withFutureActivation()->create();
    $activationDate = $stable->activations->last()->started_at;

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->currentActivation->started_at->toBeLessThan($activationDate)
        ->status->toMatchObject(StableStatus::ACTIVE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(1)
                ->status->toHaveCount(WrestlerStatus::BOOKABLE);
        })
        ->currentTagTeams->each(function ($tagTeam) {
            $tagTeam->employments->toHaveCount(1)
                ->status->toMatchObject(TagTeamStatus::BOOKABLE);
        });
});

test('invoke throws exception for activating a non activatable stable', function ($factoryState) {
    $this->withoutExceptionHandling();

    $stable = Stable::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $stable));
})->throws(CannotBeActivatedException::class)->with([
    'active',
    'retired',
]);
