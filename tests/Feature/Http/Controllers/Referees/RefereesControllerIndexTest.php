<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\RefereesController;
use App\Livewire\Referees\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Referees Controller.
 *
 * @see RefereesController
 */
describe('Referees Controller', function () {
    /**
     * @see RefereesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(route('referees.index'))
            ->assertOk()
            ->assertViewIs('referees.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see RefereesController::index()
     */
    test('a basic user cannot view referees index page', function () {
        actingAs(basicUser())
            ->get(route('referees.index'))
            ->assertForbidden();
    });

    /**
     * @see RefereesController::index()
     */
    test('a guest cannot view referees index page', function () {
        get(route('referees.index'))
            ->assertRedirect(route('login'));
    });
});
