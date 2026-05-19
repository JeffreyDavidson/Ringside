<?php

declare(strict_types=1);

use App\Http\Controllers\Events\IndexController;
use App\Livewire\Events\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Events Index Controller.
 *
 * @see IndexController
 */
describe('Events Index Controller', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('events.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view events index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view events index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});
