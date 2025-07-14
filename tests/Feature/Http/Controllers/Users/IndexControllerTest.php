<?php

declare(strict_types=1);

use App\Http\Controllers\Users\IndexController;
use App\Livewire\Users\Tables\UsersTable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Users Index Controller.
 *
 * @see IndexController
 */
describe('Users Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertSeeLivewire(UsersTable::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view Users index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view users index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});