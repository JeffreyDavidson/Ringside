<?php

use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

test('invoke retires a retirable stable and its members and redirects', function ($factoryState) {
    $stable = Stable::factory()->$factoryState()->create();

    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->retirements->toHaveCount(1)
        ->status->toBe(StableStatus::RETIRED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler
                ->retirements->toHaveCount(1)
                ->status->toBe(WrestlerStatus::RETIRED, $wrestler->status);
        })
        ->currentTagTeams->each(function ($tagTeam) {
            $tagTeam
                ->retirements->toHaveCount(1)
                ->status->toBe(TagTeamStatus::RETIRED, $tagTeam->status);
        });
})->with([
    'active',
    'inactive',
]);

test('a basic user cannot activate a stable', function () {
    $stable = Stable::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $stable))
        ->assertForbidden();
});

test('a guest cannot activate a stable', function () {
    $stable = Stable::factory()->create();

    $this->patch(action([RetireController::class], $stable))
        ->assertRedirect(route('login'));
});

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
