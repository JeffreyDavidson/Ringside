<?php

declare(strict_types=1);

use App\Http\Controllers\Users\UsersController;
use App\Livewire\Users\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Users Controller.
 *
 * @see UsersController
 */
describe('Users Controller', function () {
    /**
     * @see UsersController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([UsersController::class, 'index']))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see UsersController::index()
     */
    test('a basic user cannot view Users index page', function () {
        actingAs(basicUser())
            ->get(action([UsersController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see UsersController::index()
     */
    test('a guest cannot view users index page', function () {
        get(action([UsersController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});
