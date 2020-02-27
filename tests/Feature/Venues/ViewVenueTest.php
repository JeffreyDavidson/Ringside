<?php

namespace Tests\Feature\Venues;

use App\Enums\Role;
use Tests\TestCase;
use App\Models\Venue;
use Tests\Factories\VenueFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group venues
 */
class ViewVenueTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_administrator_can_view_a_venue()
    {
        $this->actAs(Role::ADMINISTRATOR);
        $venue = VenueFactory::new()->create();

        $response = $this->showRequest($venue);

        $response->assertViewIs('venues.show');
        $this->assertTrue($response->data('venue')->is($venue));
    }

    /** @test */
    public function a_basic_user_cannot_view_a_venue()
    {
        $this->actAs(Role::BASIC);
        $venue = VenueFactory::new()->create();

        $response = $this->showRequest($venue);

        $response->assertStatus(403);
    }

    /** @test */
    public function a_guest_cannot_view_a_venue()
    {
        $venue = VenueFactory::new()->create();

        $response = $this->showRequest($venue);

        $response->assertRedirect(route('login'));
    }
}
