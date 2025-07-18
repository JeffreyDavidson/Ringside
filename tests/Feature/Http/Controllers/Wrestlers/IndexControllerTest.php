<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\IndexController;
use App\Livewire\Wrestlers\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Wrestlers Index Controller.
 *
 * @see IndexController
 */
describe('Wrestlers Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('wrestlers.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view wrestlers index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view wrestlers index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
