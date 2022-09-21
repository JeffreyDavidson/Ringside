<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Models\TagTeam;

test('invoke reinstates a suspended tag team and its tag team partners and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($this->tagTeam->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->suspensions->last()->not->toBeNull();
            $wrestler->status->toMatchObject(WrestlerStatus::BOOKABLE);
        });
});

test('invoke throws exception for reinstating a non reinstatable tag team', function ($factoryState) {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $tagTeam));
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'withFutureEmployment',
    'unemployed',
    'released',
    'retired',
]);
