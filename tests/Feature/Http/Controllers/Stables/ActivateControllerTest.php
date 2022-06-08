<?php

use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

test('invoke activates an unactivated stable and employs its unemployed members and redirects', function () {
    $stable = Stable::factory()->unactivated()->withUnemployedDefaultMembers()->create();

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->activations->toHaveCount(1)
        ->status->toBe(StableStatus::ACTIVE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(1)
                ->status->toBe(WrestlerStatus::BOOKABLE);
        })
        ->currentTagTeams->each(function ($tagTeam) {
            $tagTeam->employments->toHaveCount(1)
                ->status->toBe(TagTeamStatus::BOOKABLE);
        });
});

test('invoke activates a future activated stable with members and redirects', function () {
    $stable = Stable::factory()->withFutureActivation()->create();
    $startedAt = $stable->activations->last()->started_at;

    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    expect($stable->fresh())
        ->currentActivation->started_at->toBeLessThan($startedAt)
        ->status->toBe(StableStatus::ACTIVE)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->employments->toHaveCount(1)
                ->status->toHaveCount(WrestlerStatus::BOOKABLE);
        })
        ->currentTagTeams->each(function ($tagTeam) {
            $tagTeam->employments->toHaveCount(1)
                ->status->toBe(TagTeamStatus::BOOKABLE);
        });
});

test('a basic user cannot activate a stable', function () {
    $stable = Stable::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([ActivateController::class], $stable))
        ->assertForbidden();
});

test('a guest cannot activate a stable', function () {
    $stable = Stable::factory()->create();

    $this->patch(action([ActivateController::class], $stable))
        ->assertRedirect(route('login'));
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
