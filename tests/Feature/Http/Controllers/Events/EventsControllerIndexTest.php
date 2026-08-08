<?php

declare(strict_types=1);

use App\Http\Controllers\Events\EventsController;
use App\Livewire\Events\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Events Controller.
 *
 * @see EventsController
 */
describe('Events Controller', function () {
    /**
     * @see EventsController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([EventsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('events.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see EventsController::index()
     */
    test('a basic user cannot view events index page', function () {
        actingAs(basicUser())
            ->get(action([EventsController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see EventsController::index()
     */
    test('a guest cannot view events index page', function () {
        get(action([EventsController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});
