<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->retired()->create();
});

test('invoke unretires a retired tag team and its tag team partners and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($this->tagTeam->fresh())
        ->retirements->last()->ended_at->not->toBeNull()
        ->status->toBe(TagTeamStatus::BOOKABLE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->retirements->last()->ended_at->not->toBeNull()
                ->status->toBe(WrestlerStatus::BOOKABLE);
        });
});

test('a basic user cannot unretire a tag team', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot unretire a tag team', function () {
    $this->patch(action([UnretireController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for unretiring a non unretirable tag team', function ($factoryState) {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $tagTeam));
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'released',
    'suspended',
    'unemployed',
]);
