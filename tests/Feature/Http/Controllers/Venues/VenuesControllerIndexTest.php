<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\VenuesController;
use App\Livewire\Venues\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Venues Controller.
 *
 * @see VenuesController
 */
describe('Venues Controller', function () {
    /**
     * @see VenuesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([VenuesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('venues.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see VenuesController::index()
     */
    test('a basic user cannot view venues index page', function () {
        actingAs(basicUser())
            ->get(action([VenuesController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see VenuesController::index()
     */
    test('a guest cannot view venues index page', function () {
        get(action([VenuesController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});
