<?php

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class VenuesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_returns_a_view()
    {
        $this
            ->actAs(Role::ADMINISTRATOR)
            ->get(action([VenuesController::class, 'index']))
            ->assertOk()
            ->assertViewIs('venues.index');
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_venues_index_page()
    {
        $this
            ->actAs(Role::BASIC)
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
            ->actAs(Role::ADMINISTRATOR)
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
            ->actAs(Role::BASIC)
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
}
