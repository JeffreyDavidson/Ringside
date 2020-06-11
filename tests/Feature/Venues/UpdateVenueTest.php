<?php

namespace Tests\Feature\Venues;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\Venue;
use Tests\Factories\VenueFactory;
use App\Http\Requests\Venues\UpdateRequest;
use App\Http\Controllers\Venues\VenuesController;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group venues
 */
class UpdateVenueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid Parameters for request.
     *
     * @param  array $overrides
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_replace([
            'name' => 'Example Venue',
            'address1' => '123 Main Street',
            'address2' => 'Suite 100',
            'city' => 'Laraville',
            'state' => 'New York',
            'zip' => '12345',
        ], $overrides);
    }

    /** @test */
    public function an_administrator_can_view_the_form_for_editing_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertViewIs('venues.edit');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $response = $this->editRequest($venue);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function an_administrator_can_update_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $venue = VenueFactory::new()->create();

        $response = $this->updateRequest($venue, $this->validParams());

        $response->assertRedirect(route('venues.index'));
        tap(Venue::first(), function ($venue) {
            $this->assertEquals('Example Venue', $venue->name);
            $this->assertEquals('123 Main Street', $venue->address1);
            $this->assertEquals('Suite 100', $venue->address2);
            $this->assertEquals('Laraville', $venue->city);
            $this->assertEquals('New York', $venue->state);
            $this->assertEquals('12345', $venue->zip);
        });
    }

    /** @test */
    public function a_basic_user_cannot_update_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $response = $this->updateRequest($venue, $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function update_validates_using_a_form_request()
    {
        $this->assertActionUsesFormRequest(
            VenuesController::class,
            'update',
            UpdateRequest::class
        );
    }
}
