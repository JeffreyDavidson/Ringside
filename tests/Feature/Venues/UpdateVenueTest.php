<?php

namespace Tests\Feature\Venues;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->get(route('venues.edit', $venue));

        $response->assertViewIs('venues.edit');
    }

    /** @test */
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $this->actAs('basic-user');
        $venue = factory(Venue::class)->create();

        $response = $this->get(route('venues.edit', $venue));

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_the_form_for_editing_a_venue()
    {
        $venue = factory(Venue::class)->create();

        $response = $this->get(route('venues.edit', $venue));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function an_administrator_can_update_a_venue()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams());

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
    public function a_venue_address2_is_optional()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams(['address2' => '']));

        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_venue()
    {
        $this->actAs('basic-user');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams());

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_update_a_venue()
    {
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams());

        $response->assertRedirect('/login');
    }

    /** @test */
    public function a_venue_name_is_required()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams(['name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function a_venue_address1_is_required()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams(['address1' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('address1');
    }

    /** @test */
    public function a_venue_city_is_required()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams(['city' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('city');
    }

    /** @test */
    public function a_venue_state_is_required()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams(['state' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('state');
    }

    /** @test */
    public function a_venue_zip_is_required()
    {
        $this->actAs('administrator');
        $venue = factory(Venue::class)->create();

        $response = $this->patch(route('venues.update', $venue), $this->validParams(['zip' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors('zip');
    }
}
