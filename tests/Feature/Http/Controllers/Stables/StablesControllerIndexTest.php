<?php

declare(strict_types=1);

use App\Http\Controllers\Stables\StablesController;
use App\Livewire\Stables\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Stables Controller.
 *
 * @see StablesController
 */
describe('Stables Controller', function () {
    /**
     * @see StablesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([StablesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('stables.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see StablesController::index()
     */
    test('a basic user cannot view stables index page', function () {
        actingAs(basicUser())
            ->get(action([StablesController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see StablesController::index()
     */
    test('a guest cannot view stables index page', function () {
        get(action([StablesController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});
