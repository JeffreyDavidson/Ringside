<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class VenuesControllerTest extends TestCase
{
    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::administrator())
            ->get(action([VenuesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('venues.index')
            ->assertSeeLivewire('venues.venues-list');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_venues_index_page()
    {
        $this
            ->actAs(Role::basic())
            ->get(action([VenuesController::class, 'index']))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_venues_index_page()
    {
        $this
            ->get(action([VenuesController::class, 'index']))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function show_returns_a_view()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([VenuesController::class, 'show'], $venue))
            ->assertViewIs('venues.show')
            ->assertViewHas('venue', $venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([VenuesController::class, 'show'], $venue))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->get(action([VenuesController::class, 'show'], $venue))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function deletes_a_venue_and_redirects()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->delete(action([VenuesController::class, 'destroy'], $venue))
            ->assertRedirect(action([VenuesController::class, 'index']));

        $this->assertSoftDeleted($venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_delete_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::basic())
            ->delete(action([VenuesController::class, 'destroy'], $venue))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_delete_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->delete(action([VenuesController::class, 'destroy'], $venue))
            ->assertRedirect(route('login'));
    }
}
