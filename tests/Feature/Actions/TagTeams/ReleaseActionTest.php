<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Models\TagTeam;

test('invoke releases a bookable tag team and tag team partners and redirects', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(TagTeamStatus::RELEASED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->status->toMatchObject(WrestlerStatus::RELEASED);
        });
});

test('invoke releases an suspended tag team and tag team partners redirects', function () {
    $tagTeam = TagTeam::factory()->suspended()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->suspensions->last()->ended_at->not->toBeNull()
        ->employments->last()->ended_at->not->toBeNull()
        ->status->toMatchObject(TagTeamStatus::RELEASED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->status->toMatchObject(WrestlerStatus::RELEASED);
        });
});

test('invoke throws an exception for releasing a non releasable tag team', function ($factoryState) {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $tagTeam));
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
