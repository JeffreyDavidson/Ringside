<?php

use App\Actions\TagTeams\EmployAction;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\Wrestler;

beforeEach(function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->unemployed()->count(2)->create();
    $this->tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->unemployed()
        ->create();

    $wrestlerA->currentTagTeam()->associate($this->tagTeam)->save();
    $wrestlerB->currentTagTeam()->associate($this->tagTeam)->save();
});

test('invoke calls employ action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    EmployAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot employ a tag team', function () {
    $this->actingAs(basicUser())
        ->patch(action([EmployController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot employ a tag team', function () {
    $this->patch(action([EmployController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
