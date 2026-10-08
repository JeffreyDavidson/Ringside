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
    /**
     * @see VenuesController::show()
     */
    test('show returns a view', function () {
        $venue = Venue::factory()->create();

        actingAs(administrator())
            ->get(route('venues.show', $venue))
            ->assertOk()
            ->assertViewIs('venues.show')
            ->assertSee($venue->name)
            ->assertSee('Address')
            ->assertViewHas('venue', $venue)
            ->assertSeeHtml('data-test="relationship-table-loading-placeholder"')
            ->assertSeeLivewire(PreviousEvents::class);
    });

    /**
     * @see VenuesController::show()
     */
    test('a basic user cannot view a venue', function () {
        $venue = Venue::factory()->create();

        actingAs(basicUser())
            ->get(route('venues.show', $venue))
            ->assertForbidden();
    });

    /**
     * @see VenuesController::show()
     */
    test('a guest cannot view a venue', function () {
        $venue = Venue::factory()->create();

        get(route('venues.show', $venue))
            ->assertRedirect(route('login'));
    });

    /**
     * @see VenuesController::show()
     */
    test('returns 404 when venue does not exist', function () {
        Venue::factory()->create();

        actingAs(administrator())
            ->get(route('venues.show', 999999))
            ->assertNotFound();
    });
});
