<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Livewire\TagTeams\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Controller.
 *
 * @see TagTeamsController
 */
describe('TagTeams Controller', function () {
    /**
     * @see TagTeamsController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(route('tag-teams.index'))
            ->assertOk()
            ->assertViewIs('tag-teams.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see TagTeamsController::index()
     */
    test('a basic user cannot view tag teams index page', function () {
        actingAs(basicUser())
            ->get(route('tag-teams.index'))
            ->assertForbidden();
    });

    /**
     * @see TagTeamsController::index()
     */
    test('a guest cannot view tag teams index page', function () {
        get(route('tag-teams.index'))
            ->assertRedirect(route('login'));
    });
});
