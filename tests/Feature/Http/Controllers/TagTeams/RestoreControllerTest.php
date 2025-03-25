<?php

declare(strict_types=1);

use App\Actions\TagTeams\RestoreAction;
use App\Http\Controllers\TagTeams\RestoreController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    RestoreAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot restore a deleted tag team', function () {
    actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot restore a deleted tag team', function () {
    patch(action([RestoreController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
