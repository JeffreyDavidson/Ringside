<?php

declare(strict_types=1);

use App\Actions\TagTeams\ReleaseAction;
use App\Http\Controllers\TagTeams\ReleaseController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->bookable()->create();
});

test('invoke calls release action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReleaseController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    ReleaseAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot release a bookable tag team', function () {
    actingAs(basicUser())
        ->patch(action([ReleaseController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot release a bookable tag team', function () {
    patch(action([ReleaseController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
