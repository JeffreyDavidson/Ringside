<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

test('invoke retires a bookable tag team and its tag team partners and redirects', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->retirements->toHaveCount(1)
        ->status->toBe(TagTeamStatus::RETIRED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler
                ->retirements->toHaveCount(1)
                ->status->toBe(WrestlerStatus::RETIRED, $wrestler->status);
        });
});

test('invoke retires an unbookable tag team and its tag team partners and redirects', function () {
    $tagTeam = TagTeam::factory()->unbookable()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->status->toBe(TagTeamStatus::RETIRED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->status->toBe(WrestlerStatus::RETIRED, $wrestler->status);
        });
});

test('a basic user cannot retire a bookable tag team', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $tagTeam))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable tag team', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->patch(action([RetireController::class], $tagTeam))
        ->assertRedirect(route('login'));
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
