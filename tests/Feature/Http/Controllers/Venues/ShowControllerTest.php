<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\ShowController;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Venues Show Controller.
 *
 * @see ShowController
 */
describe('Venues Show Controller', function () {
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
            ->assertSeeLivewire(PreviousMain::class);
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
