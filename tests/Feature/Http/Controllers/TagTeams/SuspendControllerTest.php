<?php

declare(strict_types=1);

use App\Actions\TagTeams\SuspendAction;
use App\Http\Controllers\TagTeams\SuspendController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->bookable()->create();
});

test('invoke calls suspend action and redirects', function () {
    actingAs(administrator())
        ->patch(action([SuspendController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    SuspendAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot retire a bookable tag team', function () {
    actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable tag team', function () {
    patch(action([SuspendController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
