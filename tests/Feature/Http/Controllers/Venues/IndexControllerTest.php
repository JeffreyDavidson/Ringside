<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\IndexController;
use App\Livewire\Venues\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Venues Index Controller.
 *
 * @see IndexController
 */
describe('Venues Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('venues.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view venues index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view venues index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
