<?php

declare(strict_types=1);

use App\Http\Controllers\Referees\IndexController;
use App\Livewire\Referees\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Referees Index Controller.
 *
 * @see IndexController
 */
describe('Referees Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('referees.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view referees index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view referees index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
