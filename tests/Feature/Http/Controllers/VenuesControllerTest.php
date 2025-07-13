<?php

declare(strict_types=1);

use App\Http\Controllers\VenuesController;
use App\Livewire\Venues\Tables\PreviousEventsTable;
use App\Livewire\Venues\Tables\VenuesTable;
use App\Models\Shared\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for VenuesController.
 *
 * @see VenuesController
 */
describe('index', function () {
    /**
     * @see VenuesController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([VenuesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('venues.index')
            ->assertSeeLivewire(VenuesTable::class);
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

describe('show', function () {
    beforeEach(function () {
        $this->venue = Venue::factory()->create();
    });

    /**
     * @see VenuesController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([VenuesController::class, 'show'], $this->venue))
            ->assertOk()
            ->assertViewIs('venues.show')
            ->assertViewHas('venue', $this->venue)
            ->assertSeeLivewire(PreviousEventsTable::class);
    });

    /**
     * @see VenuesController::show()
     */
    test('a basic user cannot view a venue', function () {
        actingAs(basicUser())
            ->get(action([VenuesController::class, 'show'], $this->venue))
            ->assertForbidden();
    });

    /**
     * @see VenuesController::show()
     */
    test('a guest cannot view a venue', function () {
        get(action([VenuesController::class, 'show'], $this->venue))
            ->assertRedirect(route('login'));
    });

    /**
     * @see VenuesController::show()
     */
    test('returns 404 when venue does not exist', function () {
        actingAs(administrator())
            ->get(action([VenuesController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
