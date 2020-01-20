<?php

namespace Tests\Feature\User\Venues;

use Tests\TestCase;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group venues
 * @group users
 */
class UpdateVenueFailureConditionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valid parameters for request.
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
    public function a_basic_user_cannot_view_the_form_for_editing_a_venue()
    {
        $this->actAs('basic-user');
        $venue = factory(Venue::class)->create();

        $response = $this->editRequest($venue);

        $response->assertForbidden();
    }

    /** @test */
    public function a_basic_user_cannot_update_a_venue()
    {
        $this->actAs('basic-user');
        $venue = factory(Venue::class)->create();

        $response = $this->updateRequest($venue, $this->validParams());

        $response->assertForbidden();
    }
}