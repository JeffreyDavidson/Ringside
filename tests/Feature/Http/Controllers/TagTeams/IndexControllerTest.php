<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\IndexController;
use App\Livewire\TagTeams\Tables\TagTeamsTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Index Controller.
 *
 * @see IndexController
 */
describe('TagTeams Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('tag-teams.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view tag teams index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view tag teams index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
