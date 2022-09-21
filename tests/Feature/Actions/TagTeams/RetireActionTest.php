<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Models\TagTeam;

test('invoke retires a bookable tag team and its tag team partners and redirects', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->retirements->toHaveCount(1)
        ->status->toMatchObject(TagTeamStatus::RETIRED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler
                ->retirements->toHaveCount(1)
                ->status->toMatchObject(WrestlerStatus::RETIRED, $wrestler->status);
        });
});

test('invoke retires an unbookable tag team and its tag team partners and redirects', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->status->toMatchObject(TagTeamStatus::RETIRED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->status->toMatchObject(WrestlerStatus::RETIRED, $wrestler->status);
        });
});

test('invoke throws exception for retiring a non retirable tag team', function ($factoryState) {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $tagTeam));
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureEmployment',
    'released',
    'unemployed',
]);
