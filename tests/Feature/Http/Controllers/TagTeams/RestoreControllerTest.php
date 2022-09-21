<?php

use App\Actions\TagTeams\RestoreAction;
use App\Http\Controllers\TagTeams\RestoreController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    RestoreAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot restore a deleted tag team', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot restore a deleted tag team', function () {
    $this->patch(action([RestoreController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
