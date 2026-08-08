<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\VenuesController;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Venues Controller.
 *
 * @see VenuesController
 */
describe('Venues Controller', function () {
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
            ->assertSeeLivewire(PreviousEvents::class);
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
