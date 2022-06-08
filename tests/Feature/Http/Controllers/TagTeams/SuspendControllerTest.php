<?php

use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('invoke suspends a tag team and their tag team partners and redirects', function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->bookable()
        ->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->suspensions->toHaveCount(1)
        ->status->toMatchObject(TagTeamStatus::SUSPENDED)
        ->currentWrestlers->each(function ($wrestler) {
            $wrestler->status->toMatchObject(WrestlerStatus::SUSPENDED);
        });
});

test('a basic user cannot retire a bookable tag team', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $tagTeam))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable tag team', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();

    $this->patch(action([SuspendController::class], $tagTeam))
        ->assertRedirect(route('login'));
});

test('invoke throws exception for retiring a non retirable tag team', function ($factoryState) {
    $this->withoutExceptionHandling();

    $tagTeam = TagTeam::factory()->{$factoryState}()->create();

    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $tagTeam));
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'retired',
]);
