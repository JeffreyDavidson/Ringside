<?php

declare(strict_types=1);

use App\Actions\TagTeams\RetireAction;
use App\Http\Controllers\TagTeams\RetireController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->bookable()->create();
});

test('invoke calls retire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RetireController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    RetireAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot retire a bookable tag team', function () {
    actingAs(basicUser())
        ->patch(action([RetireController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot suspend a bookable tag team', function () {
    patch(action([RetireController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
