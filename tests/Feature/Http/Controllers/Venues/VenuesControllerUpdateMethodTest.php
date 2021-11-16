<?php

namespace Tests\Feature\Http\Controllers\Venues;

use App\Enums\Role;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;
use Tests\Factories\VenueRequestDataFactory;
use Tests\TestCase;

/**
 * @group venues
 * @group feature-venues
 */
class VenuesControllerUpdateMethodTest extends TestCase
{
    /**
     * @test
     */
    public function edit_returns_a_view()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::administrator())
            ->get(action([VenuesController::class, 'edit'], $venue))
            ->assertViewIs('venues.edit')
            ->assertViewHas('venue', $venue);
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::basic())
            ->get(action([VenuesController::class, 'edit'], $venue))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->get(action([VenuesController::class, 'edit'], $venue))
            ->assertRedirect(route('login'));
    }

    /**
     * @test
     */
    public function updates_a_venue_and_redirects()
    {
        $venue = Venue::factory()->create([
            'name' => 'Old Venue Name',
            'address1' => '456 Elm Street',
            'address2' => '',
            'city' => 'Las Vegas',
            'state' => 'Nevada',
            'zip' => '67890',
        ]);

        $this
            ->actAs(Role::administrator())
            ->from(action([VenuesController::class, 'edit'], $venue))
            ->put(action([VenuesController::class, 'update'], $venue), VenueRequestDataFactory::new()->create([
                'name' => 'New Venue Name',
                'address1' => '123 Main Street',
                'address2' => 'Suite 100',
                'city' => 'Laraville',
                'state' => 'New York',
                'zip' => '12345',
            ]))
            ->assertRedirect(action([VenuesController::class, 'index']));

        tap(Venue::first(), function ($venue) {
            $this->assertEquals('New Venue Name', $venue->name);
            $this->assertEquals('123 Main Street', $venue->address1);
            $this->assertEquals('Suite 100', $venue->address2);
            $this->assertEquals('Laraville', $venue->city);
            $this->assertEquals('New York', $venue->state);
            $this->assertEquals('12345', $venue->zip);
        });
    }

    /**
     * @test
     */
    public function a_basic_user_cannot_update_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->actAs(Role::basic())
            ->from(action([VenuesController::class, 'edit'], $venue))
            ->put(action([VenuesController::class, 'update'], $venue), VenueRequestDataFactory::new()->create())
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function a_guest_cannot_update_a_venue()
    {
        $venue = Venue::factory()->create();

        $this
            ->from(action([VenuesController::class, 'edit'], $venue))
            ->put(action([VenuesController::class, 'update'], $venue), VenueRequestDataFactory::new()->create())
            ->assertRedirect(route('login'));
    }
}
