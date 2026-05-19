<?php

declare(strict_types=1);

use App\Http\Controllers\Managers\IndexController;
use App\Livewire\Managers\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Managers Index Controller.
 *
 * @see IndexController
 */
describe('Managers Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('managers.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view managers index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view managers index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
