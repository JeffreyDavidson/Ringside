<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReinstateAction;
use App\Http\Controllers\TagTeams\ReinstateController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->suspended()->create();
});

test('invoke calls reinstate action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot reinstate a suspended tag team', function () {
    actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot reinstate a suspended tag team', function () {
    patch(action([ReinstateController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
