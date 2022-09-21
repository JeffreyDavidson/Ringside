<?php

use App\Actions\TagTeams\RetireAction;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->bookable()->create();
});

test('invoke calls retire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    RetireAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot retire a bookable tag team', function () {
    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable tag team', function () {
    $this->patch(action([RetireController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
