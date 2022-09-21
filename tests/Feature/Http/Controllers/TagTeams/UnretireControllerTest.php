<?php

use App\Actions\TagTeams\UnretireAction;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Controllers\TagTeams\UnretireController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    UnretireAction::shouldRun()->with($this->tagTeam);
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
