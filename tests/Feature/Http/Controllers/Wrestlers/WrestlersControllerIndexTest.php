<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Livewire\Wrestlers\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Wrestlers Controller.
 *
 * @see WrestlersController
 */
describe('Wrestlers Controller', function () {
    /**
     * @see WrestlersController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([WrestlersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see WrestlersController::index()
     */
    test('a basic user cannot view wrestlers index page', function () {
        actingAs(basicUser())
            ->get(action([WrestlersController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see WrestlersController::index()
     */
    test('a guest cannot view wrestlers index page', function () {
        get(action([WrestlersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});
