<?php

use App\Actions\TagTeams\ReinstateAction;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->suspended()->create();
});

test('invoke calls reinstate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->tagTeam);
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
