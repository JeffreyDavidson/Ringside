<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\IndexController;
use App\Http\Controllers\Venues\ShowController;
use App\Livewire\Venues\Tables\PreviousEventsTable;
use App\Livewire\Venues\Tables\VenuesTable;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Venues Controllers.
 *
 * @see IndexController
 * @see ShowController
 */
describe('index', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('venues.index')
            ->assertSeeLivewire(VenuesTable::class);
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

describe('show', function () {
    beforeEach(function () {
        $this->venue = Venue::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->venue))
            ->assertOk()
            ->assertViewIs('venues.show')
            ->assertViewHas('venue', $this->venue)
            ->assertSeeLivewire(PreviousEventsTable::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view a venue', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->venue))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a venue', function () {
        get(action(ShowController::class, $this->venue))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when venue does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
