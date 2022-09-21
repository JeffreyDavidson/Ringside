<?php

use App\Actions\TagTeams\SuspendAction;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->bookable()->create();
});

test('invoke calls suspend action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    SuspendAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot retire a bookable tag team', function () {
    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable tag team', function () {
    $this->patch(action([SuspendController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
