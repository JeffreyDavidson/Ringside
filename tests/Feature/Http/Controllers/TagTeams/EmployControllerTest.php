<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Http\Controllers\TagTeams\EmployController;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->unemployed()->count(2)->create();
    $this->tagTeam = TagTeam::factory()
        ->hasAttached($wrestlerA, ['joined_at' => now()->toDateTimeString()])
        ->hasAttached($wrestlerB, ['joined_at' => now()->toDateTimeString()])
        ->unemployed()
        ->create();
});

test('invoke calls employ action and redirects', function () {
    actingAs(administrator())
        ->patch(action([EmployController::class], $this->tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    EmployAction::shouldRun()->with($this->tagTeam);
});

test('a basic user cannot employ a tag team', function () {
    actingAs(basicUser())
        ->patch(action([EmployController::class], $this->tagTeam))
        ->assertForbidden();
});

test('a guest cannot employ a tag team', function () {
    patch(action([EmployController::class], $this->tagTeam))
        ->assertRedirect(route('login'));
});
