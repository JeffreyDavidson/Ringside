<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\ManagersController;
use App\Livewire\Managers\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Managers Controller.
 *
 * @see ManagersController
 */
describe('Managers Controller', function () {
    /**
     * @see ManagersController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([ManagersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('managers.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see ManagersController::index()
     */
    test('a basic user cannot view managers index page', function () {
        actingAs(basicUser())
            ->get(action([ManagersController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see ManagersController::index()
     */
    test('a guest cannot view managers index page', function () {
        get(action([ManagersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});
