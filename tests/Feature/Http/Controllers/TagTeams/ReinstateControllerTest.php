<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->suspended()->create();
});

test('invoke reinstates a suspended tag team and its tag team partners and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($this->tagTeam->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->status->toBe(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->suspensions->last()->not->toBeNull();
            $wrestler->status->toBe(WrestlerStatus::BOOKABLE);
        });
});

test('a basic user cannot reinstate a suspended tag team', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot reinstate a suspended tag team', function () {
    $this->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
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
